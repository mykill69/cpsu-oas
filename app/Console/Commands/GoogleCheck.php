<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;

/** Prints the exact values to register in Google Cloud and checks what's still missing. */
class GoogleCheck extends Command
{
    protected $signature = 'oas:google-check';

    protected $description = 'Show the Google redirect URIs to register and check the Google/Drive setup';

    public function handle(GoogleDrive $drive): int
    {
        $base = rtrim(config('app.url'), '/');
        $domain = config('oas.allowed_domain');

        $this->info('1) Open the system at exactly this address (it must match Google):');
        $this->line("   {$base}");
        $this->newLine();
        $this->info('2) Authorized redirect URIs to paste in Google Cloud > Credentials > your OAuth client:');
        $this->line("   {$base}/auth/google/callback");
        $this->line("   {$base}/qa/drive/callback");
        $this->newLine();

        $realQa = User::role(['qa_director', 'administrator'])->get()
            ->filter(fn ($u) => ! in_array($u->email, ['qa_director@cpsu.edu.ph', 'admin@cpsu.edu.ph'], true));

        $checks = [
            ['APP_URL is the address you really open (plain http://localhost usually is not)', $base !== 'http://localhost'],
            ['GOOGLE_CLIENT_ID set', filled(config('services.google.client_id'))],
            ['GOOGLE_CLIENT_SECRET set', filled(config('services.google.client_secret'))],
            ["Allowed domain: @{$domain}", filled($domain)],
            ['Drive account: ' . config('oas.drive_account'), str_ends_with(config('oas.drive_account'), '@' . $domain)],
            ['A real QA/admin account is registered (not only the sample ones)', $realQa->isNotEmpty()],
            ['Google Drive connected' . ($drive->connectedEmail() ? ' as ' . $drive->connectedEmail() : ''), $drive->isConnected()],
            config('mail.default') === 'gmail'
                ? ['No-reply email via Gmail as ' . config('mail.from.address') . ' (needs Reconnect if ✘)', $drive->canSendMail()]
                : ['Mail is not the Docker default (mailpit)', config('mail.mailers.smtp.host') !== 'mailpit'],
            ['Password sign-in is off', ! config('oas.allow_password_login')],
        ];

        $this->info('3) Status:');
        foreach ($checks as [$label, $ok]) {
            $this->line(($ok ? '   <fg=green>✔</> ' : '   <fg=yellow>✘</> ') . $label);
        }

        return self::SUCCESS;
    }
}
