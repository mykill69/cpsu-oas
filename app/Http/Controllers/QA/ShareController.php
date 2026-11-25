<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Mail\ShareInvitation;
use App\Models\College;
use App\Models\SurveyVisit;
use App\Models\VisitShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** QA office grants accreditors read-only access to one college's binder for one visit. */
class ShareController extends Controller
{
    public function index()
    {
        $year = \App\Models\SchoolYear::viewing();
        $shares = VisitShare::whereHas('surveyVisit', fn ($v) => $v->where('school_year_id', $year->id))
            ->with(['college', 'surveyVisit', 'creator'])->latest()->get();

        return view('qa.shares.index', compact('shares', 'year'));
    }

    public function store(Request $request, College $college, SurveyVisit $surveyVisit)
    {
        abort_unless($surveyVisit->colleges()->whereKey($college->id)->exists(), 404);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email:rfc|max:255',
            'expires_at' => 'required|date|after:today|before:' . now()->addYear()->toDateString(),
            'note' => 'nullable|string|max:1000',
        ], [
            'expires_at.after' => 'Access has to last at least until tomorrow.',
        ]);

        $share = new VisitShare($data + [
            'college_id' => $college->id,
            'survey_visit_id' => $surveyVisit->id,
            'created_by' => $request->user()->id,
        ]);
        $share->expires_at = \Carbon\Carbon::parse($data['expires_at'])->endOfDay();

        return $this->sendInvitation($share, "Shared with {$share->name}.");
    }

    /** New link and new code. The old link stops working. */
    public function resend(VisitShare $share)
    {
        abort_unless($share->isUsable(), 422, 'This share has ended. Create a new one.');

        return $this->sendInvitation($share, "A new link and code were sent to {$share->email}.");
    }

    public function revoke(VisitShare $share)
    {
        $share->forceFill(['revoked_at' => now(), 'otp_hash' => null])->save();

        return back()->with('success', "{$share->name} no longer has access.");
    }

    private function sendInvitation(VisitShare $share, string $ok)
    {
        $token = $share->issueToken();
        $share->save();
        $code = $share->issueOtp();

        try {
            Mail::to($share->email, $share->name)->send(new ShareInvitation($share->load('college', 'surveyVisit'), route('review.show', $token), $code));
        } catch (\Throwable $e) {
            Log::error('Share invitation failed', ['share' => $share->id, 'error' => $e->getMessage()]);
            $share->forceFill(['email_error' => mb_substr($e->getMessage(), 0, 500)])->save();

            return back()->with('error', "The share was saved, but the email to {$share->email} could not be sent. Check the mail settings, then use “Resend”.")
                ->with('sent_share', $share->id)->withFragment('share');
        }

        $share->forceFill(['emailed_at' => now(), 'email_error' => null])->save();

        return back()->with('success', $ok . " The link and a one-time code were sent to {$share->email}.")
            ->with('sent_share', $share->id)->withFragment('share');
    }
}
