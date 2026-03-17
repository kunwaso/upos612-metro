<?php

namespace Modules\ProjectX\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\ProjectX\Entities\Quote;

class QuoteConfirmedMail extends Mailable
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
        $subject = $this->options['subject'] ?? __('projectx::lang.quote_confirmed_email_subject', ['quote' => $quoteNo]);

        return $this->subject($subject)
            ->view('projectx::quotes.mail_confirmed')
            ->with([
                'quote' => $this->quote,
                'quoteNo' => $quoteNo,
            ]);
    }
}

