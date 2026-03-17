<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\ProductQuote;

class QuotePublicLinkMail extends Mailable
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
        $subject = $this->options['subject'] ?? __('product.quote_email_subject', ['quote' => $quoteNo]);
        $publicUrl = $this->options['public_url'] ?? route('product.quotes.public', ['publicToken' => $this->quote->public_token]);

        return $this->subject($subject)
            ->view('emails.quote_public_link')
            ->with([
                'quote' => $this->quote,
                'quoteNo' => $quoteNo,
                'publicUrl' => $publicUrl,
            ]);
    }
}
