<?php

namespace App\Utils;

use App\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorUtil
{
    public const OTP_DIGITS = 6;

    public const OTP_STEP_SECONDS = 30;

    public const OTP_VERIFICATION_WINDOW = 1;

    public const RECOVERY_CODES_COUNT = 10;

    public const SETUP_CACHE_TTL_MINUTES = 15;

    public const CHALLENGE_MAX_ATTEMPTS = 5;

    public const CHALLENGE_LOCK_MINUTES = 10;

    public const SETUP_CACHE_PREFIX = 'two_factor.setup.';

    public const CHALLENGE_ATTEMPTS_CACHE_PREFIX = 'two_factor.challenge.attempts.';

    public const CHALLENGE_LOCK_CACHE_PREFIX = 'two_factor.challenge.lock.';

    protected const CHALLENGE_IP_HASHES_CACHE_PREFIX = 'two_factor.challenge.iphashes.';

    protected const RECOVERY_DOWNLOAD_CACHE_PREFIX = 'two_factor.recovery.download.';

    protected const SESSION_VERIFIED_USER_ID = 'two_factor.verified_user_id';

    protected const SESSION_VERIFIED_AT = 'two_factor.verified_at';

    protected Google2FA $google2fa;

    public function __construct(?Google2FA $google2fa = null)
    {
        $this->google2fa = $google2fa ?: new Google2FA();
        $this->google2fa->setOneTimePasswordLength(self::OTP_DIGITS);
        $this->google2fa->setKeyRegeneration(self::OTP_STEP_SECONDS);
        $this->google2fa->setWindow(self::OTP_VERIFICATION_WINDOW);
    }

    public function generateSecret(int $length = 32): string
    {
        return $this->google2fa->generateSecretKey($length);
    }

    public function buildProvisioningUri(User $user, string $secret): string
    {
        $issuer = (string) config('app.name', 'UPOS');
        $holder = (string) ($user->username ?: $user->email ?: ('user-'.$user->id));

        return $this->google2fa->getQRCodeUrl($issuer, $holder, $secret);
    }

    public function renderProvisioningQrDataUri(string $uri, int $size = 220): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 2),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($uri);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function normalizeTotpCode(string $code): string
    {
        return preg_replace('/\D+/', '', trim($code)) ?? '';
    }

    public function verifyTotpCode(string $secret, string $code): bool
    {
        $normalized = $this->normalizeTotpCode($code);
        if (strlen($normalized) !== self::OTP_DIGITS) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $normalized, self::OTP_VERIFICATION_WINDOW);
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = self::RECOVERY_CODES_COUNT): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = strtoupper(Str::random(8));
            $codes[] = substr($raw, 0, 4).'-'.substr($raw, 4, 4);
        }

        return $codes;
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(function ($code) {
            return Hash::make($this->normalizeRecoveryCode((string) $code));
        }, array_values($codes));
    }

    public function consumeRecoveryCode(User $user, string $inputCode): bool
    {
        $codes = $this->decodeRecoveryCodes($user->two_factor_recovery_codes);
        if (empty($codes)) {
            return false;
        }

        $normalized = $this->normalizeRecoveryCode($inputCode);
        foreach ($codes as $index => $hashedCode) {
            if (! is_string($hashedCode)) {
                continue;
            }

            if (Hash::check($normalized, $hashedCode)) {
                unset($codes[$index]);
                $user->two_factor_recovery_codes = $this->encodeRecoveryCodes($codes);
                $user->save();

                return true;
            }
        }

        return false;
    }

    public function recoveryCodesCount(?string $encodedCodes): int
    {
        return count($this->decodeRecoveryCodes($encodedCodes));
    }

    /**
     * @param  array<int, string>  $codes
     */
    public function encodeRecoveryCodes(array $codes): string
    {
        return (string) json_encode(array_values($codes));
    }

    /**
     * @return array<int, string>
     */
    public function decodeRecoveryCodes(?string $encodedCodes): array
    {
        if (empty($encodedCodes)) {
            return [];
        }

        $decoded = json_decode($encodedCodes, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, 'is_string'));
    }

    public function setupCacheKey(int $userId): string
    {
        return self::SETUP_CACHE_PREFIX.$userId;
    }

    public function storeSetupSecret(int $userId, string $secret): void
    {
        Cache::put(
            $this->setupCacheKey($userId),
            [
                'secret' => $secret,
                'created_at' => now()->toIso8601String(),
            ],
            now()->addMinutes(self::SETUP_CACHE_TTL_MINUTES)
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSetupPayload(int $userId): ?array
    {
        $payload = Cache::get($this->setupCacheKey($userId));
        if (! is_array($payload) || empty($payload['secret'])) {
            return null;
        }

        return $payload;
    }

    public function clearSetup(int $userId): void
    {
        Cache::forget($this->setupCacheKey($userId));
    }

    public function challengeAttemptsCacheKey(int $userId, string $ipHash): string
    {
        return self::CHALLENGE_ATTEMPTS_CACHE_PREFIX.$userId.'.'.$ipHash;
    }

    public function challengeLockCacheKey(int $userId, string $ipHash): string
    {
        return self::CHALLENGE_LOCK_CACHE_PREFIX.$userId.'.'.$ipHash;
    }

    public function hashIpAddress(?string $ipAddress): string
    {
        return sha1((string) ($ipAddress ?: 'unknown'));
    }

    public function isChallengeLocked(int $userId, ?string $ipAddress): bool
    {
        $ipHash = $this->hashIpAddress($ipAddress);

        return Cache::has($this->challengeLockCacheKey($userId, $ipHash));
    }

    public function challengeAttemptCount(int $userId, ?string $ipAddress): int
    {
        $ipHash = $this->hashIpAddress($ipAddress);

        return (int) Cache::get($this->challengeAttemptsCacheKey($userId, $ipHash), 0);
    }

    public function remainingChallengeAttempts(int $userId, ?string $ipAddress): int
    {
        return max(0, self::CHALLENGE_MAX_ATTEMPTS - $this->challengeAttemptCount($userId, $ipAddress));
    }

    public function incrementChallengeAttempts(int $userId, ?string $ipAddress): int
    {
        $ipHash = $this->hashIpAddress($ipAddress);
        $attemptsKey = $this->challengeAttemptsCacheKey($userId, $ipHash);
        $lockKey = $this->challengeLockCacheKey($userId, $ipHash);

        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addMinutes(self::CHALLENGE_LOCK_MINUTES));
        $this->rememberChallengeIpHash($userId, $ipHash);

        if ($attempts >= self::CHALLENGE_MAX_ATTEMPTS) {
            Cache::put($lockKey, true, now()->addMinutes(self::CHALLENGE_LOCK_MINUTES));
        }

        return $attempts;
    }

    public function clearChallengeRateLimit(int $userId, ?string $ipAddress): void
    {
        $ipHash = $this->hashIpAddress($ipAddress);
        Cache::forget($this->challengeAttemptsCacheKey($userId, $ipHash));
        Cache::forget($this->challengeLockCacheKey($userId, $ipHash));
    }

    public function clearChallengeRateLimitForUser(int $userId): void
    {
        $ipHashes = $this->getRememberedChallengeIpHashes($userId);
        foreach ($ipHashes as $ipHash) {
            Cache::forget($this->challengeAttemptsCacheKey($userId, $ipHash));
            Cache::forget($this->challengeLockCacheKey($userId, $ipHash));
        }

        Cache::forget($this->challengeIpHashesCacheKey($userId));
    }

    public function markTwoFactorVerified(Session $session, int $userId): void
    {
        $session->put(self::SESSION_VERIFIED_USER_ID, $userId);
        $session->put(self::SESSION_VERIFIED_AT, now()->toIso8601String());
    }

    public function hasVerifiedTwoFactor(Session $session, int $userId): bool
    {
        return (int) $session->get(self::SESSION_VERIFIED_USER_ID) === $userId;
    }

    public function clearVerifiedTwoFactor(Session $session): void
    {
        $session->forget(self::SESSION_VERIFIED_USER_ID);
        $session->forget(self::SESSION_VERIFIED_AT);
    }

    /**
     * @param  array<int, string>  $codes
     */
    public function storeRecoveryDownloadPayload(int $userId, string $token, array $codes): void
    {
        Cache::put(
            $this->recoveryDownloadCacheKey($userId, $token),
            array_values($codes),
            now()->addMinutes(self::SETUP_CACHE_TTL_MINUTES)
        );
    }

    /**
     * @return array<int, string>|null
     */
    public function consumeRecoveryDownloadPayload(int $userId, string $token): ?array
    {
        $cacheKey = $this->recoveryDownloadCacheKey($userId, $token);
        $codes = Cache::get($cacheKey);
        Cache::forget($cacheKey);

        if (! is_array($codes)) {
            return null;
        }

        return array_values(array_filter($codes, 'is_string'));
    }

    protected function challengeIpHashesCacheKey(int $userId): string
    {
        return self::CHALLENGE_IP_HASHES_CACHE_PREFIX.$userId;
    }

    protected function rememberChallengeIpHash(int $userId, string $ipHash): void
    {
        $cacheKey = $this->challengeIpHashesCacheKey($userId);
        $ipHashes = $this->getRememberedChallengeIpHashes($userId);
        if (! in_array($ipHash, $ipHashes, true)) {
            $ipHashes[] = $ipHash;
        }

        Cache::put($cacheKey, $ipHashes, now()->addMinutes(self::CHALLENGE_LOCK_MINUTES));
    }

    /**
     * @return array<int, string>
     */
    protected function getRememberedChallengeIpHashes(int $userId): array
    {
        $ipHashes = Cache::get($this->challengeIpHashesCacheKey($userId), []);
        if (! is_array($ipHashes)) {
            return [];
        }

        return array_values(array_filter($ipHashes, 'is_string'));
    }

    protected function recoveryDownloadCacheKey(int $userId, string $token): string
    {
        return self::RECOVERY_DOWNLOAD_CACHE_PREFIX.$userId.'.'.$token;
    }

    protected function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', trim($code)) ?? '');
    }
}
