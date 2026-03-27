<?php

namespace Modules\Mailbox\Services;

use Illuminate\Support\Str;
use Modules\Mailbox\Entities\MailboxAccount;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;

class SmtpMailboxSender
{
    public function send(MailboxAccount $account, array $payload): array
    {
        $email = new Email();
        $email->from(new Address((string) $account->email_address, (string) ($account->sender_name ?: $account->display_name ?: $account->email_address)));
        $email->subject((string) ($payload['subject'] ?? ''));
        $email->to(...$this->mapAddresses((array) ($payload['to'] ?? [])));

        if (! empty($payload['cc'])) {
            $email->cc(...$this->mapAddresses((array) $payload['cc']));
        }

        if (! empty($payload['bcc'])) {
            $email->bcc(...$this->mapAddresses((array) $payload['bcc']));
        }

        $html = (string) ($payload['body_html'] ?? '');
        $email->html($html !== '' ? $html : '<p></p>');
        $email->text(trim(strip_tags($html)) !== '' ? trim(strip_tags($html)) : ' ');

        $internetMessageId = (string) ($payload['internet_message_id'] ?? sprintf('<%s@%s>', (string) Str::uuid(), $this->messageIdDomain($account)));
        $headers = $email->getHeaders();
        if (! $headers->has('Message-ID')) {
            $headers->addIdHeader('Message-ID', trim($internetMessageId, '<>'));
        }

        if (! empty($payload['in_reply_to'])) {
            $headers->addTextHeader('In-Reply-To', (string) $payload['in_reply_to']);
        }

        if (! empty($payload['references'])) {
            $headers->addTextHeader('References', is_array($payload['references']) ? implode(' ', $payload['references']) : (string) $payload['references']);
        }

        foreach ((array) ($payload['attachments'] ?? []) as $attachment) {
            if (! empty($attachment['path']) && is_file((string) $attachment['path'])) {
                $email->attachFromPath(
                    (string) $attachment['path'],
                    (string) ($attachment['name'] ?? basename((string) $attachment['path'])),
                    (string) ($attachment['mime_type'] ?? 'application/octet-stream')
                );
            }
        }

        $transport = new EsmtpTransport(
            (string) $account->smtp_host,
            (int) $account->smtp_port,
            strtolower((string) $account->smtp_encryption) === 'ssl'
        );

        if (! empty($account->smtp_username)) {
            $transport->setUsername((string) $account->smtp_username);
        }

        if (! empty($account->encrypted_smtp_password)) {
            $transport->setPassword((string) $account->encrypted_smtp_password);
        }

        (new Mailer($transport))->send($email);

        return [
            'internet_message_id' => $internetMessageId,
            'provider_message_id' => 'smtp:' . trim($internetMessageId, '<>'),
        ];
    }

    protected function mapAddresses(array $emails): array
    {
        return collect($emails)
            ->map(function ($email) {
                return new Address((string) $email);
            })
            ->all();
    }

    protected function messageIdDomain(MailboxAccount $account): string
    {
        $parts = explode('@', (string) $account->email_address);

        return $parts[1] ?? 'localhost';
    }
}
