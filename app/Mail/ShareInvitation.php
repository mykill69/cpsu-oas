<?php

namespace App\Mail;

use App\Models\VisitShare;
use Illuminate\Mail\Mailable;

/** Sent to an accreditor: the private link plus a one-time code (or just a fresh code when $fresh). */
class ShareInvitation extends Mailable
{
    public function __construct(public VisitShare $share, public string $link, public string $code, public bool $fresh = false)
    {
    }

    public function build()
    {
        $visit = $this->share->surveyVisit;

        // Automated, no-reply message: mail clients and auto-responders shouldn't answer it.
        $this->withSymfonyMessage(function ($message) {
            $message->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
            $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'All');
        });

        return $this->subject($this->fresh
                ? "Your new access code · {$this->share->college->code} {$visit->name}"
                : "Accreditation documents shared with you · {$this->share->college->code} {$visit->name}")
            ->view('emails.share-invitation');
    }
}
