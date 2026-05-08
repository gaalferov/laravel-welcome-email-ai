<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

class WelcomeMailer
{
    public function send(string $recipientEmail, array $templateVariables): void
    {
        $templateUuid = config('welcome.mailtrap_template_uuid');

        if (! $templateUuid) {
            Log::warning('Mailtrap template UUID not configured, skipping welcome email', [
                'recipient' => $recipientEmail,
            ]);

            return;
        }

        $isSandbox = (bool) config('services.mailtrap.sandbox');
        $inboxId = config('services.mailtrap.inbox_id');

        if ($isSandbox && empty($inboxId)) {
            Log::warning('Sandbox mode is on but MAILTRAP_INBOX_ID is not set, skipping welcome email', [
                'recipient' => $recipientEmail,
            ]);

            return;
        }

        $email = (new MailtrapEmail)
            ->from(new Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ))
            ->to(new Address($recipientEmail))
            ->templateUuid($templateUuid)
            ->templateVariables($templateVariables);

        $client = MailtrapClient::initSendingEmails(
            apiKey: config('services.mailtrap.api_key'),
            isSandbox: $isSandbox,
            inboxId: $isSandbox ? (int) $inboxId : null,
        );

        $client->send($email);

        Log::info('Welcome email sent', [
            'recipient' => $recipientEmail,
            'mode' => $isSandbox ? 'sandbox' : 'production',
        ]);
    }
}
