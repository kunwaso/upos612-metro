<?php

namespace App\Contracts;

use App\ProductQuote;

interface QuoteMailerInterface
{
    public function sendQuoteEmail(ProductQuote $quote, string $to, array $options = []): void;

    public function sendQuoteConfirmationEmail(ProductQuote $quote, string $to, array $options = []): void;
}
