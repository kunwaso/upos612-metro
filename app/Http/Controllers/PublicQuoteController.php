<?php

namespace App\Http\Controllers;

use App\Contracts\QuoteMailerInterface;
use App\Http\Requests\ConfirmPublicQuoteRequest;
use App\Http\Requests\UnlockPublicQuoteRequest;
use App\ProductQuote;
use App\Utils\QuoteDisplayPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class PublicQuoteController extends Controller
{
    protected const MAX_FAILED_ATTEMPTS = 5;

    protected const COOLDOWN_MINUTES = 10;

    protected QuoteMailerInterface $quoteMailer;

    protected QuoteDisplayPresenter $quoteDisplayPresenter;

    public function __construct(QuoteMailerInterface $quoteMailer, QuoteDisplayPresenter $quoteDisplayPresenter)
    {
        $this->quoteMailer = $quoteMailer;
        $this->quoteDisplayPresenter = $quoteDisplayPresenter;
    }

    public function show(Request $request, string $publicToken)
    {
        $quote = $this->findQuoteByPublicToken($publicToken);

        if (! $quote) {
            abort(404);
        }

        if ($quote->expires_at && $quote->expires_at->isPast()) {
            abort(404);
        }

        if ($this->isPasswordProtected($quote) && ! $this->isQuoteUnlocked($publicToken, $quote)) {
            $isLocked = $this->isQuoteUnlockBlocked($publicToken, $request->ip());
            $unlockInputMode = $this->resolvedPublicQuoteUnlockInputMode();
            $unlockOtpLength = $this->resolvedPublicQuoteUnlockOtpLength();

            return response()
                ->view('quotes.public_quote_password', [
                    'publicToken' => $publicToken,
                    'isLocked' => $isLocked,
                    'lockedMessage' => $isLocked ? __('product.quote_unlock_blocked_10min') : null,
                    'unlockInputMode' => $unlockInputMode,
                    'unlockOtpLength' => $unlockOtpLength,
                    'unlockOtpDigitsOnly' => (bool) config('product.public_quote_unlock.otp_digits_only', true),
                    'unlockFormAction' => route('product.quotes.public.unlock', ['publicToken' => $publicToken]),
                ])
                ->setStatusCode($isLocked ? 429 : 200)
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        $quoteDisplay = $this->quoteDisplayPresenter->presentPublicQuote($quote);

        return response()
            ->view('quotes.public_quote', compact('quote', 'quoteDisplay'))
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function confirm(ConfirmPublicQuoteRequest $request, string $publicToken)
    {
        $quote = $this->findQuoteByPublicToken($publicToken);

        if (! $quote) {
            abort(404);
        }

        if ($quote->expires_at && $quote->expires_at->isPast()) {
            abort(404);
        }

        if ($this->isPasswordProtected($quote) && ! $this->isQuoteUnlocked($publicToken, $quote)) {
            return redirect()
                ->route('product.quotes.public', ['publicToken' => $publicToken])
                ->with('status', ['success' => false, 'msg' => __('product.enter_password_to_view_quote')]);
        }

        if ($quote->confirmed_at) {
            return redirect()
                ->route('product.quotes.public', ['publicToken' => $publicToken])
                ->with('status', ['success' => false, 'msg' => __('product.quote_already_confirmed')]);
        }

        $quote->confirmation_signature = (string) $request->input('signature');
        $quote->confirmed_at = now();
        $quote->save();

        if (! empty(optional($quote->creator)->email)) {
            try {
                $this->quoteMailer->sendQuoteConfirmationEmail(
                    $quote,
                    (string) $quote->creator->email
                );
            } catch (\Exception $e) {
                \Log::warning('Product quote confirmation mail failed', [
                    'quote_id' => $quote->id,
                    'creator_id' => $quote->created_by,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('product.quotes.public', ['publicToken' => $publicToken])
            ->with('status', ['success' => true, 'msg' => __('product.quote_confirmed_success')]);
    }

    public function unlock(UnlockPublicQuoteRequest $request, string $publicToken)
    {
        $quote = $this->findQuoteByPublicToken($publicToken);

        if (! $quote) {
            abort(404);
        }

        if ($quote->expires_at && $quote->expires_at->isPast()) {
            abort(404);
        }

        if (! $this->isPasswordProtected($quote)) {
            return redirect()->route('product.quotes.public', ['publicToken' => $publicToken]);
        }

        if ($this->isQuoteUnlockBlocked($publicToken, $request->ip())) {
            return redirect()
                ->route('product.quotes.public', ['publicToken' => $publicToken])
                ->withErrors([
                    'password' => __('product.quote_unlock_blocked_10min'),
                ]);
        }

        if (! Hash::check((string) $request->input('password'), (string) $quote->public_link_password)) {
            $attempts = $this->incrementQuoteUnlockAttempts($publicToken, $request->ip());
            $message = $attempts >= self::MAX_FAILED_ATTEMPTS
                ? __('product.quote_too_many_failed_attempts')
                : __('product.invalid_quote_password');

            return redirect()
                ->route('product.quotes.public', ['publicToken' => $publicToken])
                ->withErrors([
                    'password' => $message,
                ]);
        }

        $this->clearQuoteUnlockAttempts($publicToken, $request->ip());
        $this->markQuoteUnlocked($publicToken, $quote);

        return redirect()->route('product.quotes.public', ['publicToken' => $publicToken]);
    }

    protected function unlockSessionKey(string $publicToken): string
    {
        return 'product_quote_unlocked_token_' . $publicToken;
    }

    protected function passwordFingerprint(ProductQuote $quote): string
    {
        return hash('sha256', (string) $quote->public_link_password);
    }

    protected function isPasswordProtected(ProductQuote $quote): bool
    {
        return ! empty($quote->public_link_password);
    }

    protected function isQuoteUnlocked(string $publicToken, ProductQuote $quote): bool
    {
        if (! $this->isPasswordProtected($quote)) {
            return true;
        }

        $sessionFingerprint = (string) session($this->unlockSessionKey($publicToken), '');
        $currentFingerprint = $this->passwordFingerprint($quote);

        return $sessionFingerprint !== '' && hash_equals($currentFingerprint, $sessionFingerprint);
    }

    protected function markQuoteUnlocked(string $publicToken, ProductQuote $quote): void
    {
        if (! $this->isPasswordProtected($quote)) {
            session()->forget($this->unlockSessionKey($publicToken));

            return;
        }

        session()->put($this->unlockSessionKey($publicToken), $this->passwordFingerprint($quote));
    }

    protected function findQuoteByPublicToken(string $publicToken): ?ProductQuote
    {
        return ProductQuote::with([
            'business:id,name,tax_label_1,tax_number_1,tax_label_2,tax_number_2,currency_id,quantity_precision,currency_precision',
            'location:id,name,landmark,city,state,country,zip_code,mobile,alternate_number,email',
            'contact:id,name,supplier_business_name',
            'creator:id,surname,first_name,last_name,email,username',
            'lines.product:id,name,sku',
        ])->where('public_token', $publicToken)->first();
    }

    protected function passwordAttemptsKey(string $publicToken, ?string $ipAddress): string
    {
        return 'product.quote_unlock_attempts.' . $publicToken . '.' . sha1((string) ($ipAddress ?: 'unknown'));
    }

    protected function passwordCooldownKey(string $publicToken, ?string $ipAddress): string
    {
        return 'product.quote_unlock_cooldown.' . $publicToken . '.' . sha1((string) ($ipAddress ?: 'unknown'));
    }

    protected function isQuoteUnlockBlocked(string $publicToken, ?string $ipAddress): bool
    {
        return Cache::has($this->passwordCooldownKey($publicToken, $ipAddress));
    }

    protected function incrementQuoteUnlockAttempts(string $publicToken, ?string $ipAddress): int
    {
        $attemptsKey = $this->passwordAttemptsKey($publicToken, $ipAddress);
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;

        Cache::put($attemptsKey, $attempts, now()->addMinutes(self::COOLDOWN_MINUTES));

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            Cache::put($this->passwordCooldownKey($publicToken, $ipAddress), true, now()->addMinutes(self::COOLDOWN_MINUTES));
        }

        return $attempts;
    }

    protected function clearQuoteUnlockAttempts(string $publicToken, ?string $ipAddress): void
    {
        Cache::forget($this->passwordAttemptsKey($publicToken, $ipAddress));
        Cache::forget($this->passwordCooldownKey($publicToken, $ipAddress));
    }

    protected function resolvedPublicQuoteUnlockInputMode(): string
    {
        $mode = strtolower((string) config('product.public_quote_unlock.input_mode', 'password'));

        return in_array($mode, ['password', 'otp'], true) ? $mode : 'password';
    }

    protected function resolvedPublicQuoteUnlockOtpLength(): int
    {
        return min(32, max(1, (int) config('product.public_quote_unlock.otp_length', 6)));
    }
}
