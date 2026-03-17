<?php

namespace Modules\ProjectX\Services;

use Illuminate\Support\Facades\Mail;
use Modules\ProjectX\Contracts\QuoteMailerInterface;
use Modules\ProjectX\Entities\Quote;
use Modules\ProjectX\Mail\QuoteConfirmedMail;
use Modules\ProjectX\Mail\QuotePublicLinkMail;

class QuoteMailerLaravel implements QuoteMailerInterface
{
    public function sendQuoteEmail(Quote $quote, string $to, array $options = []): void
    {
        Mail::to($to)->send(new QuotePublicLinkMail($quote, $options));
    }

    public function sendQuoteConfirmationEmail(Quote $quote, string $to, array $options = []): void
    {
        Mail::to($to)->send(new QuoteConfirmedMail($quote, $options));
    }
}
