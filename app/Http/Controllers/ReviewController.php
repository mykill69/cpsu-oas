<?php

namespace App\Http\Controllers;

use App\Mail\ShareInvitation;
use App\Models\Area;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Parameter;
use App\Models\VisitShare;
use App\Support\BinderLinks;
use App\Support\BinderPages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The accreditor's side of a share: link + one-time code, then read-only browsing of
 * exactly one college's binder for one visit. No account; access lives in the session.
 */
class ReviewController extends Controller
{
    public function show(Request $request, string $token)
    {
        $share = $this->find($token);
        if ($share->isUsable() && $this->verified($request, $share)) {
            return redirect()->route('review.visit', $token);
        }

        return view('review.gate', ['share' => $share->load('college', 'surveyVisit'), 'token' => $token]);
    }

    public function verify(Request $request, string $token)
    {
        $share = $this->find($token);
        abort_unless($share->isUsable(), 410);
        $request->validate(['code' => 'required|digits:6']);

        if (! $share->consumeOtp($request->input('code'))) {
            $locked = $share->fresh()->otp_attempts >= VisitShare::OTP_MAX_ATTEMPTS;

            return back()->withErrors(['code' => $locked
                ? 'Too many wrong tries. Request a new code below.'
                : 'That code is wrong or has expired.']);
        }

        $request->session()->regenerate();
        $request->session()->put($this->key($share), true);

        return redirect()->route('review.visit', $token);
    }

    public function resend(Request $request, string $token)
    {
        $share = $this->find($token);
        abort_unless($share->isUsable(), 410);

        if (! $share->canResend()) {
            return back()->with('error', 'A code was just sent. Wait a minute before asking for another.');
        }

        $code = $share->issueOtp();
        try {
            Mail::to($share->email, $share->name)->send(new ShareInvitation($share->load('college', 'surveyVisit'), route('review.show', $token), $code, true));
        } catch (\Throwable $e) {
            Log::error('Share code resend failed', ['share' => $share->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'The code could not be emailed right now. Please contact the CPSU QA office.');
        }

        return back()->with('success', 'A new code was sent to ' . $this->mask($share->email) . '.');
    }

    public function visit(Request $request, string $token)
    {
        $share = $this->open($request, $token);
        $share->forceFill(['last_viewed_at' => now(), 'view_count' => $share->view_count + 1])->save();

        return view('binder.visit', BinderPages::visit($share->college, $share->surveyVisit) + $this->context($share, $token));
    }

    public function node(Request $request, string $token, Area $area, ?Parameter $parameter = null)
    {
        $share = $this->open($request, $token);
        abort_unless($area->survey_visit_id == $share->survey_visit_id, 404);

        return view('binder.folder', BinderPages::node($share->college, $area, $parameter) + $this->context($share, $token));
    }

    public function folder(Request $request, string $token, Folder $folder)
    {
        $share = $this->open($request, $token);
        $this->inScope($share, $folder);

        return view('binder.folder', BinderPages::folder($folder) + $this->context($share, $token));
    }

    public function document(Request $request, string $token, Document $document)
    {
        $share = $this->open($request, $token);
        $this->inScope($share, $document->folder);

        return DocumentController::respond($request, $document);
    }

    public function leave(Request $request, string $token)
    {
        $share = $this->find($token);
        $request->session()->forget($this->key($share));

        return redirect()->route('review.show', $token)->with('success', 'You have left the shared documents.');
    }

    private function find(string $token): VisitShare
    {
        $share = VisitShare::findByToken($token);
        abort_unless($share, 404, 'This link is not valid. It may have been replaced by a newer one.');

        return $share;
    }

    /** A usable share whose code was verified in this browser session. */
    private function open(Request $request, string $token): VisitShare
    {
        $share = $this->find($token);
        if (! $share->isUsable() || ! $this->verified($request, $share)) {
            abort(redirect()->route('review.show', $token));
        }

        return $share->load('college', 'surveyVisit');
    }

    private function inScope(VisitShare $share, Folder $folder): void
    {
        abort_unless($folder->college_id == $share->college_id
            && Area::whereKey($folder->area_id)->where('survey_visit_id', $share->survey_visit_id)->exists(), 404);
    }

    private function verified(Request $request, VisitShare $share): bool
    {
        return (bool) $request->session()->get($this->key($share));
    }

    private function key(VisitShare $share): string
    {
        return 'review_share.' . $share->id;
    }

    private function context(VisitShare $share, string $token): array
    {
        return ['mode' => 'review', 'canEdit' => false, 'links' => new BinderLinks($token), 'share' => $share, 'token' => $token];
    }

    private function mask(string $email): string
    {
        [$user, $domain] = explode('@', $email) + [1 => ''];

        return mb_substr($user, 0, 2) . str_repeat('•', max(1, mb_strlen($user) - 2)) . '@' . $domain;
    }
}
