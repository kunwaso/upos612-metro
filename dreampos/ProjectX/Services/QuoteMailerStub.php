<?php

namespace Modules\ProjectX\Services;

use Illuminate\Support\Facades\Log;
use Modules\ProjectX\Contracts\QuoteMailerInterface;
use Modules\ProjectX\Entities\Quote;

class QuoteMailerStub implements QuoteMailerInterface
{
    public function sendQuoteEmail(Quote $quote, string $to, array $options = []): void
    {
        Log::info('ProjectX quote mail stub send', [
            'quote_id' => $quote->id,
            'quote_uuid' => $quote->uuid,
            'quote_number' => $quote->quote_number,
            'to' => $to,
            'public_token' => $quote->public_token,
            'options' => $options,
        ]);
    }

    public function sendQuoteConfirmationEmail(Quote $quote, string $to, array $options = []): void
    {
        Log::info('ProjectX quote confirmation mail stub send', [
            'quote_id' => $quote->id,
            'quote_uuid' => $quote->uuid,
            'quote_number' => $quote->quote_number,
            'to' => $to,
            'public_token' => $quote->public_token,
            'options' => $options,
        ]);
    }
}
