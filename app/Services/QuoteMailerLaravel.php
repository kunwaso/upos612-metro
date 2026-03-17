<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Contracts\QuoteMailerInterface;
use App\ProductQuote;
use App\Mail\QuoteConfirmedMail;
use App\Mail\QuotePublicLinkMail;

class QuoteMailerLaravel implements QuoteMailerInterface
{
    public function sendQuoteEmail(ProductQuote $quote, string $to, array $options = []): void
    {
        Mail::to($to)->send(new QuotePublicLinkMail($quote, $options));
    }

    public function sendQuoteConfirmationEmail(ProductQuote $quote, string $to, array $options = []): void
    {
        Mail::to($to)->send(new QuoteConfirmedMail($quote, $options));
    }
}
