<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\ProductQuote;

class QuoteConfirmedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public ProductQuote $quote;

    public array $options;

    public function __construct(ProductQuote $quote, array $options = [])
    {
        $this->quote = $quote;
        $this->options = $options;
    }

    public function build()
    {
        $quoteNo = $this->quote->quote_number ?: $this->quote->uuid;
        $subject = $this->options['subject'] ?? __('product.quote_confirmed_email_subject', ['quote' => $quoteNo]);

        return $this->subject($subject)
            ->view('emails.quote_confirmed')
            ->with([
                'quote' => $this->quote,
                'quoteNo' => $quoteNo,
            ]);
    }
}

