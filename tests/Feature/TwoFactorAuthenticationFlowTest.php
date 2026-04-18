<?php

namespace Tests\Feature;

use App\Http\Controllers\UserTwoFactorController;
use App\User;
use App\Utils\TwoFactorUtil;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TwoFactorAuthenticationFlowTest extends TestCase
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

        Schema::dropIfExists('activity_log');
        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->unsignedInteger('business_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_owner_can_start_and_confirm_two_factor_setup(): void
    {
        $controller = app(UserTwoFactorController::class);
        $twoFactorUtil = app(TwoFactorUtil::class);

        $user = $this->createUser(1, 10);

        $startRequest = $this->makeRequest($user, 'POST', '/users/10/two-factor/setup');
        $startResponse = $controller->startSetup($startRequest, $user->id);

        $this->assertSame(302, $startResponse->status());
        $setupPayload = $twoFactorUtil->getSetupPayload((int) $user->id);
        $this->assertNotNull($setupPayload);
        $this->assertArrayHasKey('secret', $setupPayload);

        $otp = (new Google2FA())->getCurrentOtp((string) $setupPayload['secret']);
        $confirmRequest = $this->makeRequest($user, 'POST', '/users/10/two-factor/confirm', [
            'code' => $otp,
        ]);
        $confirmResponse = $controller->confirmSetup($confirmRequest, $user->id);

        $this->assertSame(302, $confirmResponse->status());
        $user->refresh();

        $this->assertTrue((bool) $user->two_factor_enabled);
        $this->assertNotNull($user->two_factor_confirmed_at);
        $storedRecoveryCodes = $twoFactorUtil->decodeRecoveryCodes($user->two_factor_recovery_codes);
        $this->assertCount(10, $storedRecoveryCodes);
        $this->assertStringStartsWith('$2y$', $storedRecoveryCodes[0]);
    }

    public function test_non_owner_cannot_start_two_factor_setup(): void
    {
        $controller = app(UserTwoFactorController::class);
        $owner = $this->createUser(1, 20);
        $target = $this->createUser(1, 21);

        $request = $this->makeRequest($owner, 'POST', '/users/21/two-factor/setup');

        try {
            $controller->startSetup($request, $target->id);
            $this->fail('Expected a 403 HttpException.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_download_recovery_codes_is_one_time_only(): void
    {
        $controller = app(UserTwoFactorController::class);
        $twoFactorUtil = app(TwoFactorUtil::class);
        $user = $this->createUser(1, 30);
        $token = 'recovery-token-123';

        $twoFactorUtil->storeRecoveryDownloadPayload((int) $user->id, $token, ['ABCD-EFGH']);

        $downloadRequest = $this->makeRequest($user, 'GET', '/users/30/two-factor/recovery-codes/'.$token);
        $firstResponse = $controller->downloadRecoveryCodes($downloadRequest, $user->id, $token);

        $this->assertSame(200, $firstResponse->getStatusCode());
        $this->assertStringContainsString('ABCD-EFGH', $firstResponse->getContent());

        $secondRequest = $this->makeRequest($user, 'GET', '/users/30/two-factor/recovery-codes/'.$token);
        $secondResponse = $controller->downloadRecoveryCodes($secondRequest, $user->id, $token);

        $this->assertSame(302, $secondResponse->getStatusCode());
    }

    public function test_superadmin_reset_clears_two_factor_state_and_locks(): void
    {
        $controller = app(UserTwoFactorController::class);
        $twoFactorUtil = app(TwoFactorUtil::class);
        $target = $this->createUser(1, 40, [
            'two_factor_enabled' => 1,
            'two_factor_secret' => $twoFactorUtil->generateSecret(),
            'two_factor_recovery_codes' => $twoFactorUtil->encodeRecoveryCodes(
                $twoFactorUtil->hashRecoveryCodes(['ABCD-EFGH'])
            ),
            'two_factor_confirmed_at' => now(),
        ]);

        $twoFactorUtil->storeSetupSecret((int) $target->id, $twoFactorUtil->generateSecret());
        for ($i = 0; $i < TwoFactorUtil::CHALLENGE_MAX_ATTEMPTS; $i++) {
            $twoFactorUtil->incrementChallengeAttempts((int) $target->id, '127.0.0.1');
        }
        $this->assertTrue($twoFactorUtil->isChallengeLocked((int) $target->id, '127.0.0.1'));

        $superadmin = new class extends \Illuminate\Foundation\Auth\User
        {
            public int $id = 999;

            public function can($abilities, $arguments = []): bool
            {
                return in_array('superadmin', (array) $abilities, true);
            }
        };

        $resetRequest = $this->makeRequest($superadmin, 'POST', '/users/40/two-factor/reset');
        $resetResponse = $controller->resetForUser($resetRequest, $target->id);

        $this->assertSame(302, $resetResponse->status());
        $target->refresh();

        $this->assertFalse((bool) $target->two_factor_enabled);
        $this->assertNull($target->two_factor_secret);
        $this->assertNull($target->two_factor_recovery_codes);
        $this->assertNull($target->two_factor_confirmed_at);
        $this->assertNull($twoFactorUtil->getSetupPayload((int) $target->id));
        $this->assertFalse($twoFactorUtil->isChallengeLocked((int) $target->id, '127.0.0.1'));
    }

    protected function createUser(int $businessId, int $id, array $overrides = []): User
    {
        return User::create(array_merge([
            'id' => $id,
            'business_id' => $businessId,
            'surname' => 'Mr',
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'user'.$id,
            'email' => 'user'.$id.'@example.com',
            'password' => Hash::make('secret123'),
            'allow_login' => 1,
            'status' => 'active',
            'user_type' => 'user',
            'two_factor_enabled' => 0,
        ], $overrides));
    }

    protected function makeRequest($actingUser, string $method, string $uri, array $data = []): Request
    {
        $request = Request::create($uri, $method, $data);
        $request->server->set('HTTP_REFERER', '/users');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $session = $this->app['session']->driver();
        $session->start();
        $session->put('user.business_id', 1);
        $session->put('business', (object) ['id' => 1, 'time_zone' => 'UTC']);
        $request->setLaravelSession($session);
        $request->setUserResolver(function ($guard = null) use ($actingUser) {
            return $actingUser;
        });

        $this->app->instance('request', $request);
        if ($actingUser instanceof Authenticatable) {
            auth()->setUser($actingUser);
        } else {
            app('auth')->forgetGuards();
        }

        return $request;
    }
}
