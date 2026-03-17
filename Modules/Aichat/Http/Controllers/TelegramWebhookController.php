<?php

namespace Modules\Aichat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Aichat\Jobs\ProcessTelegramWebhookJob;
use Modules\Aichat\Utils\ChatUtil;

class TelegramWebhookController extends Controller
{
    protected ChatUtil $chatUtil;

    public function __construct(ChatUtil $chatUtil)
    {
        $this->chatUtil = $chatUtil;
    }

    public function webhook(Request $request, string $webhookKey): Response
    {
        $bot = $this->chatUtil->findTelegramBotByWebhookKey($webhookKey);
        if (! $bot) {
            return response('OK', 200);
        }

        $rateKey = 'aichat:telegram:webhook:' . $webhookKey;
        $maxAttempts = (int) config('aichat.telegram.webhook_rate_limit_per_minute', 60);
        if (RateLimiter::tooManyAttempts($rateKey, max(1, $maxAttempts))) {
            return response('OK', 200);
        }
        RateLimiter::hit($rateKey, 60);

        $expectedSecret = (string) ($bot->webhook_secret_token ?? '');
        if ($expectedSecret !== '') {
            $headerSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($expectedSecret, $headerSecret)) {
                return response('OK', 200);
            }
        }

        $update = $request->all();
        if (! is_array($update) || ! isset($update['message'])) {
            return response('OK', 200);
        }

        ProcessTelegramWebhookJob::dispatch($webhookKey, $update);

        return response('OK', 200);
    }
}
