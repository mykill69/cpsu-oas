<?php

namespace App\Http\Controllers\QA;

use App\Http\Controllers\Controller;
use App\Jobs\SyncToDrive;
use App\Models\Document;
use App\Models\Folder;
use App\Services\DriveSync;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

/** Connect the system Google account whose Drive stores every uploaded file. */
class DriveController extends Controller
{
    // drive.file: only files this system created. gmail.send: send the no-reply emails (cannot read the inbox).
    private const SCOPES = ['openid', 'email', 'https://www.googleapis.com/auth/drive.file', GoogleDrive::GMAIL_SEND_SCOPE];

    public function index(GoogleDrive $drive)
    {
        return view('qa.drive.index', [
            'configured' => $drive->isConfigured(),
            'connected' => $drive->isConnected(),
            'email' => $drive->connectedEmail(),
            'canMail' => $drive->canSendMail(),
            'mailer' => config('mail.default'),
            'account' => config('oas.drive_account'),
            'pending' => Document::whereNull('drive_id')->count(),
            'failed' => Document::whereNull('drive_id')->whereNotNull('drive_error')->latest()->take(10)->get(),
            'synced' => Document::whereNotNull('drive_id')->count(),
            'queue' => config('queue.default'),
        ]);
    }

    public function connect(GoogleDrive $drive)
    {
        abort_unless($drive->isConfigured(), 422, 'Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to .env first.');

        return Socialite::driver('google')
            ->redirectUrl(route('qa.drive.callback'))
            ->scopes(self::SCOPES)
            ->with(['access_type' => 'offline', 'prompt' => 'consent', 'login_hint' => config('oas.drive_account')])
            ->redirect();
    }

    public function callback(GoogleDrive $drive, DriveSync $sync)
    {
        try {
            $google = Socialite::driver('google')->redirectUrl(route('qa.drive.callback'))->user();
        } catch (\Throwable $e) {
            return redirect()->route('qa.drive.index')->with('error', 'Google did not finish connecting. Please try again.');
        }

        $email = mb_strtolower((string) $google->getEmail());
        if ($email !== mb_strtolower(config('oas.drive_account'))) {
            return redirect()->route('qa.drive.index')->with('error', "You signed in as {$email}. Connect with " . config('oas.drive_account') . ' instead.');
        }
        if (! $google->refreshToken) {
            return redirect()->route('qa.drive.index')->with('error', 'Google returned no offline access. Remove the app under the account\'s Google security settings, then connect again.');
        }

        $drive->saveConnection($email, $google->refreshToken, (array) $google->approvedScopes);
        $sync->rootId(); // creates the top-level "CPSU OAS Accreditation Files" folder

        return redirect()->route('qa.drive.index')->with('success', "Google Drive connected as {$email}.");
    }

    /** Push folders and files that haven't reached Drive yet (a batch per click). */
    public function sync(GoogleDrive $drive)
    {
        abort_unless($drive->isConnected(), 422, 'Connect Google Drive first.');

        $folders = Folder::whereNull('drive_id')->orderBy('id')->limit(100)->pluck('id');
        $docs = Document::whereNull('drive_id')->whereNotNull('path')->orderBy('id')->limit(25)->pluck('id');
        foreach ($folders as $id) {
            SyncToDrive::send('folder', $id);
        }
        foreach ($docs as $id) {
            SyncToDrive::send('document', $id);
        }

        return back()->with('success', "Sending {$folders->count()} folders and {$docs->count()} files to Drive. Refresh in a moment.");
    }

    public function disconnect(GoogleDrive $drive)
    {
        $drive->disconnect();

        return back()->with('success', 'Google Drive disconnected. New uploads stay on the server until it is connected again.');
    }
}
