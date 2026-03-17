<?php

namespace Modules\ProjectX\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\ProjectX\Entities\Quote;

class QuotePublicLinkMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public Quote $quote;

    public array $options;

    public function __construct(Quote $quote, array $options = [])
    {
        $this->quote = $quote;
        $this->options = $options;
    }

    public function build()
    {
        $quoteNo = $this->quote->quote_number ?: $this->quote->uuid;
        $subject = $this->options['subject'] ?? __('projectx::lang.quote_email_subject', ['quote' => $quoteNo]);
        $publicUrl = $this->options['public_url'] ?? route('projectx.quotes.public', ['publicToken' => $this->quote->public_token]);

        return $this->subject($subject)
            ->view('projectx::quotes.mail_public_link')
            ->with([
                'quote' => $this->quote,
                'quoteNo' => $quoteNo,
                'publicUrl' => $publicUrl,
            ]);
    }
}
