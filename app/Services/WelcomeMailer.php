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

        $email = (new MailtrapEmail())
            ->from(new Address(
                config('mail.from.address'),
                config('mail.from.name'),
            ))
            ->to(new Address($recipientEmail))
            ->templateUuid($templateUuid)
            ->templateVariables($templateVariables);

        $isSandbox = (bool) config('welcome.sandbox');
        $inboxId = (int) config('welcome.inbox_id');

        $client = MailtrapClient::initSendingEmails(
            apiKey: config('services.mailtrap.apiKey'),
            isSandbox: $isSandbox,
            inboxId: $isSandbox ? $inboxId : null,
        );

        $client->send($email);

        Log::info('Welcome email sent', [
            'recipient' => $recipientEmail,
            'mode' => $isSandbox ? 'sandbox' : 'production',
        ]);
    }
}
