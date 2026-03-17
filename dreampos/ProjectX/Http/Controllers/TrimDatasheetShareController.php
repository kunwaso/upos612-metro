<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Modules\ProjectX\Http\Requests\VerifyTrimSharePasswordRequest;
use Modules\ProjectX\Utils\TrimManagerUtil;

class TrimDatasheetShareController extends Controller
{
    protected const MAX_FAILED_ATTEMPTS = 5;
    protected const COOLDOWN_MINUTES = 15;

    protected const VERIFIED_COOKIE_NAME = 'projectx_trim_share_verified';
    protected const VERIFIED_COOKIE_MINUTES = 60 * 24;

    protected TrimManagerUtil $trimUtil;

    public function __construct(TrimManagerUtil $trimUtil)
    {
        $this->trimUtil = $trimUtil;
    }

    public function show(Request $request, string $token)
    {
        $trim = $this->trimUtil->getTrimByShareToken($token);
        if (! $trim) {
            return $this->renderDenied(__('projectx::lang.share_link_invalid'), 404);
        }

        if (! $trim->share_enabled) {
            return $this->renderDenied(__('projectx::lang.share_link_disabled'));
        }

        if (! empty($trim->share_expires_at) && now()->greaterThan($trim->share_expires_at)) {
            return $this->renderDenied(__('projectx::lang.share_link_expired'));
        }

        if ($this->trimUtil->isShareRateLimitExceeded($trim->id, $trim->share_rate_limit_per_day)) {
            return $this->renderDenied(__('projectx::lang.share_rate_limit_exceeded'), 429);
        }

        if (! empty($trim->share_password_hash) && ! $this->isShareVerified($request, $token)) {
            $locked = $this->isPasswordAttemptsBlocked($token, $request->ip());

            return $this->renderGate(
                $token,
                $locked ? __('projectx::lang.share_too_many_failed_attempts') : null,
                $locked,
                $locked ? 429 : 200
            );
        }

        $this->trimUtil->recordShareView($trim->id, $request->ip());

        $fds = $this->trimUtil->buildTrimDatasheetPayload($trim);
        $fds['context'] = 'public';

        return view('projectx::trims.datasheet_public', compact('fds'));
    }

    public function verifyPassword(VerifyTrimSharePasswordRequest $request, string $token)
    {
        $trim = $this->trimUtil->getTrimByShareToken($token);
        if (! $trim) {
            return $this->renderDenied(__('projectx::lang.share_link_invalid'), 404);
        }

        if (! $trim->share_enabled) {
            return $this->renderDenied(__('projectx::lang.share_link_disabled'));
        }

        if (! empty($trim->share_expires_at) && now()->greaterThan($trim->share_expires_at)) {
            return $this->renderDenied(__('projectx::lang.share_link_expired'));
        }

        if ($this->trimUtil->isShareRateLimitExceeded($trim->id, $trim->share_rate_limit_per_day)) {
            return $this->renderDenied(__('projectx::lang.share_rate_limit_exceeded'), 429);
        }

        if (empty($trim->share_password_hash)) {
            $this->markShareVerified($request, $token);

            return redirect()->route('projectx.trim_manager.datasheet.share', ['token' => $token]);
        }

        if ($this->isPasswordAttemptsBlocked($token, $request->ip())) {
            return $this->renderGate($token, __('projectx::lang.share_too_many_failed_attempts'), true, 429);
        }

        if (! Hash::check((string) $request->input('password'), (string) $trim->share_password_hash)) {
            $attempts = $this->incrementPasswordAttempts($token, $request->ip());
            $locked = $attempts >= self::MAX_FAILED_ATTEMPTS;
            $message = $locked
                ? __('projectx::lang.share_too_many_failed_attempts')
                : __('projectx::lang.share_incorrect_password');

            return $this->renderGate($token, $message, $locked, 422);
        }

        $this->clearPasswordAttempts($token, $request->ip());
        $this->markShareVerified($request, $token);

        return redirect()->route('projectx.trim_manager.datasheet.share', ['token' => $token]);
    }

    protected function renderGate(string $token, ?string $errorMessage = null, bool $locked = false, int $status = 200)
    {
        return response()->view('projectx::trims.datasheet_share_gate', [
            'token' => $token,
            'errorMessage' => $errorMessage,
            'locked' => $locked,
        ], $status);
    }

    protected function renderDenied(string $message, int $status = 403)
    {
        return response()->view('projectx::trims.datasheet_share_denied', [
            'message' => $message,
        ], $status);
    }

    protected function verificationSessionKey(string $token): string
    {
        return 'projectx.trim_share_verified.' . $token;
    }

    protected function markShareVerified(Request $request, string $token): void
    {
        $request->session()->put($this->verificationSessionKey($token), true);

        Cookie::queue(
            Cookie::make(
                self::VERIFIED_COOKIE_NAME,
                Crypt::encryptString($token),
                self::VERIFIED_COOKIE_MINUTES,
                '/',
                null,
                $request->secure(),
                true,
                false,
                'lax'
            )
        );
    }

    protected function isShareVerified(Request $request, string $token): bool
    {
        if ((bool) $request->session()->get($this->verificationSessionKey($token), false)) {
            return true;
        }

        $cookieValue = $request->cookie(self::VERIFIED_COOKIE_NAME);
        if (empty($cookieValue)) {
            return false;
        }

        try {
            return Crypt::decryptString($cookieValue) === $token;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return false;
        }
    }

    protected function passwordAttemptsKey(string $token, ?string $ipAddress): string
    {
        return 'projectx.trim_share_attempts.' . $token . '.' . sha1((string) ($ipAddress ?: 'unknown'));
    }

    protected function passwordCooldownKey(string $token, ?string $ipAddress): string
    {
        return 'projectx.trim_share_cooldown.' . $token . '.' . sha1((string) ($ipAddress ?: 'unknown'));
    }

    protected function isPasswordAttemptsBlocked(string $token, ?string $ipAddress): bool
    {
        return Cache::has($this->passwordCooldownKey($token, $ipAddress));
    }

    protected function incrementPasswordAttempts(string $token, ?string $ipAddress): int
    {
        $attemptsKey = $this->passwordAttemptsKey($token, $ipAddress);
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;

        Cache::put($attemptsKey, $attempts, now()->addMinutes(self::COOLDOWN_MINUTES));

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            Cache::put($this->passwordCooldownKey($token, $ipAddress), true, now()->addMinutes(self::COOLDOWN_MINUTES));
        }

        return $attempts;
    }

    protected function clearPasswordAttempts(string $token, ?string $ipAddress): void
    {
        Cache::forget($this->passwordAttemptsKey($token, $ipAddress));
        Cache::forget($this->passwordCooldownKey($token, $ipAddress));
    }
}
