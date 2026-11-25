<?php

namespace App\Mail;

use App\Services\GoogleDrive;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Laravel mailer "gmail": sends through the Gmail API as the connected system
 * account (cpsu_accresys@…), reusing the Google connection made on the
 * Google Drive page. No SMTP password is stored anywhere.
 */
class GmailApiTransport extends AbstractTransport
{
    public function __construct(private GoogleDrive $google)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (! $this->google->canSendMail()) {
            throw new \RuntimeException('Email sending is not allowed yet. On the Google Drive page, click Reconnect and allow "Send email on your behalf".');
        }

        $this->google->sendRawEmail($message->toString());
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
