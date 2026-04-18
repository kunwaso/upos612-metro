<?php

namespace Tests\Unit;

use App\User;
use App\Utils\TwoFactorUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->default(1);
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('allow_login')->default(1);
            $table->string('status')->default('active');
            $table->string('user_type')->default('user');
            $table->boolean('two_factor_enabled')->default(0);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_it_verifies_totp_with_six_digits_and_30_second_window(): void
    {
        $util = app(TwoFactorUtil::class);
        $google2fa = new Google2FA();
        $google2fa->setOneTimePasswordLength(TwoFactorUtil::OTP_DIGITS);
        $google2fa->setKeyRegeneration(TwoFactorUtil::OTP_STEP_SECONDS);
        $google2fa->setWindow(TwoFactorUtil::OTP_VERIFICATION_WINDOW);

        $secret = $util->generateSecret();
        $validCode = $google2fa->getCurrentOtp($secret);
        $invalidCode = $validCode === '000000' ? '111111' : '000000';

        $this->assertTrue($util->verifyTotpCode($secret, $validCode));
        $this->assertFalse($util->verifyTotpCode($secret, $invalidCode));
    }

    public function test_recovery_code_is_consumed_once_only(): void
    {
        $util = app(TwoFactorUtil::class);
        $recoveryCode = 'ABCD-EFGH';
        $user = User::create([
            'id' => 51,
            'business_id' => 1,
            'surname' => 'Mr',
            'first_name' => 'Recovery',
            'last_name' => 'Tester',
            'username' => 'recovery-user',
            'email' => 'recovery@example.com',
            'password' => Hash::make('secret123'),
            'allow_login' => 1,
            'status' => 'active',
            'user_type' => 'user',
            'two_factor_enabled' => 1,
            'two_factor_recovery_codes' => $util->encodeRecoveryCodes(
                $util->hashRecoveryCodes([$recoveryCode])
            ),
        ]);

        $this->assertTrue($util->consumeRecoveryCode($user, $recoveryCode));
        $user->refresh();
        $this->assertSame(0, $util->recoveryCodesCount($user->two_factor_recovery_codes));
        $this->assertFalse($util->consumeRecoveryCode($user, $recoveryCode));
    }

    public function test_lockout_applies_after_five_failed_attempts_and_clears(): void
    {
        $util = app(TwoFactorUtil::class);
        $userId = 999;
        $ip = '127.0.0.1';

        for ($i = 1; $i <= 4; $i++) {
            $attempts = $util->incrementChallengeAttempts($userId, $ip);
            $this->assertSame($i, $attempts);
            $this->assertFalse($util->isChallengeLocked($userId, $ip));
        }

        $fifthAttempt = $util->incrementChallengeAttempts($userId, $ip);
        $this->assertSame(TwoFactorUtil::CHALLENGE_MAX_ATTEMPTS, $fifthAttempt);
        $this->assertTrue($util->isChallengeLocked($userId, $ip));

        $util->clearChallengeRateLimit($userId, $ip);
        $this->assertFalse($util->isChallengeLocked($userId, $ip));
        $this->assertSame(0, $util->challengeAttemptCount($userId, $ip));
    }
}
