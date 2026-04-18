<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\User;
use App\Utils\TwoFactorUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorChallengeControllerTest extends TestCase
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

    public function test_valid_totp_code_marks_session_as_verified(): void
    {
        $controller = app(TwoFactorChallengeController::class);
        $twoFactorUtil = app(TwoFactorUtil::class);
        $secret = $twoFactorUtil->generateSecret();
        $user = $this->createUser(1, 80, [
            'two_factor_enabled' => 1,
            'two_factor_secret' => $secret,
        ]);

        $google2fa = new Google2FA();
        $google2fa->setOneTimePasswordLength(TwoFactorUtil::OTP_DIGITS);
        $google2fa->setKeyRegeneration(TwoFactorUtil::OTP_STEP_SECONDS);
        $google2fa->setWindow(TwoFactorUtil::OTP_VERIFICATION_WINDOW);
        $otp = $google2fa->getCurrentOtp($secret);

        $request = $this->makeRequest($user, 'POST', '/two-factor/challenge', [
            'code' => $otp,
        ]);
        $response = $controller->verify($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue($twoFactorUtil->hasVerifiedTwoFactor($request->session(), (int) $user->id));
    }

    public function test_five_failed_attempts_lock_the_challenge(): void
    {
        $controller = app(TwoFactorChallengeController::class);
        $twoFactorUtil = app(TwoFactorUtil::class);
        $user = $this->createUser(1, 81, [
            'two_factor_enabled' => 1,
            'two_factor_secret' => $twoFactorUtil->generateSecret(),
        ]);

        for ($i = 0; $i < TwoFactorUtil::CHALLENGE_MAX_ATTEMPTS; $i++) {
            $request = $this->makeRequest($user, 'POST', '/two-factor/challenge', [
                'code' => '000000',
            ]);
            $controller->verify($request);
        }

        $this->assertTrue($twoFactorUtil->isChallengeLocked((int) $user->id, '127.0.0.1'));

        $validRequest = $this->makeRequest($user, 'POST', '/two-factor/challenge', [
            'code' => '123456',
        ]);
        $controller->verify($validRequest);
        $this->assertFalse($twoFactorUtil->hasVerifiedTwoFactor($validRequest->session(), (int) $user->id));
    }

    public function test_recovery_code_works_once_only(): void
    {
        $controller = app(TwoFactorChallengeController::class);
        $twoFactorUtil = app(TwoFactorUtil::class);
        $recoveryCode = 'ABCD-EFGH';
        $user = $this->createUser(1, 82, [
            'two_factor_enabled' => 1,
            'two_factor_secret' => $twoFactorUtil->generateSecret(),
            'two_factor_recovery_codes' => $twoFactorUtil->encodeRecoveryCodes(
                $twoFactorUtil->hashRecoveryCodes([$recoveryCode])
            ),
        ]);

        $firstRequest = $this->makeRequest($user, 'POST', '/two-factor/challenge/recovery', [
            'recovery_code' => $recoveryCode,
        ]);
        $controller->verifyRecoveryCode($firstRequest);
        $this->assertTrue($twoFactorUtil->hasVerifiedTwoFactor($firstRequest->session(), (int) $user->id));

        $twoFactorUtil->clearVerifiedTwoFactor($firstRequest->session());
        $secondRequest = $this->makeRequest($user, 'POST', '/two-factor/challenge/recovery', [
            'recovery_code' => $recoveryCode,
        ]);
        $controller->verifyRecoveryCode($secondRequest);
        $this->assertFalse($twoFactorUtil->hasVerifiedTwoFactor($secondRequest->session(), (int) $user->id));
    }

    protected function createUser(int $businessId, int $id, array $overrides = []): User
    {
        return User::create(array_merge([
            'id' => $id,
            'business_id' => $businessId,
            'surname' => 'Mr',
            'first_name' => 'Challenge',
            'last_name' => 'Tester',
            'username' => 'challenge'.$id,
            'email' => 'challenge'.$id.'@example.com',
            'password' => Hash::make('secret123'),
            'allow_login' => 1,
            'status' => 'active',
            'user_type' => 'user',
            'two_factor_enabled' => 0,
        ], $overrides));
    }

    protected function makeRequest(User $user, string $method, string $uri, array $data = []): Request
    {
        $request = Request::create($uri, $method, $data);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->server->set('HTTP_REFERER', '/two-factor/challenge');

        $session = $this->app['session']->driver();
        $session->start();
        $request->setLaravelSession($session);
        $request->setUserResolver(function ($guard = null) use ($user) {
            return $user;
        });

        $this->app->instance('request', $request);
        auth()->setUser($user);

        return $request;
    }
}
