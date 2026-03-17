<?php

namespace Modules\ProjectX\Contracts;

use Modules\ProjectX\Entities\Quote;

interface QuoteMailerInterface
{
    public function sendQuoteEmail(Quote $quote, string $to, array $options = []): void;

    public function sendQuoteConfirmationEmail(Quote $quote, string $to, array $options = []): void;
}
