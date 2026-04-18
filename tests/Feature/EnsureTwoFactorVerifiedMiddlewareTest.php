<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTwoFactorVerified;
use App\User;
use App\Utils\TwoFactorUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnsureTwoFactorVerifiedMiddlewareTest extends TestCase
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

    public function test_enabled_user_without_verified_session_is_redirected_to_challenge(): void
    {
        $middleware = app(EnsureTwoFactorVerified::class);
        $request = $this->makeRequest($this->createUser(1, 70, ['two_factor_enabled' => 1]));

        $response = $middleware->handle($request, static fn () => response('OK'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/two-factor/challenge', $response->headers->get('Location'));
    }

    public function test_impersonation_session_bypasses_two_factor_check(): void
    {
        $middleware = app(EnsureTwoFactorVerified::class);
        $request = $this->makeRequest($this->createUser(1, 71, ['two_factor_enabled' => 1]));
        $request->session()->put('previous_user_id', 1);

        $response = $middleware->handle($request, static fn () => response('OK'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    public function test_verified_session_for_current_user_bypasses_challenge(): void
    {
        $middleware = app(EnsureTwoFactorVerified::class);
        $twoFactorUtil = app(TwoFactorUtil::class);
        $user = $this->createUser(1, 72, ['two_factor_enabled' => 1]);
        $request = $this->makeRequest($user);
        $twoFactorUtil->markTwoFactorVerified($request->session(), (int) $user->id);

        $response = $middleware->handle($request, static fn () => response('OK'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }

    protected function createUser(int $businessId, int $id, array $overrides = []): User
    {
        return User::create(array_merge([
            'id' => $id,
            'business_id' => $businessId,
            'surname' => 'Mr',
            'first_name' => 'Middleware',
            'last_name' => 'Tester',
            'username' => 'middleware'.$id,
            'email' => 'middleware'.$id.'@example.com',
            'password' => Hash::make('secret123'),
            'allow_login' => 1,
            'status' => 'active',
            'user_type' => 'user',
            'two_factor_enabled' => 0,
        ], $overrides));
    }

    protected function makeRequest(User $user): Request
    {
        $request = Request::create('/home', 'GET');
        $session = $this->app['session']->driver();
        $session->start();
        $request->setLaravelSession($session);
        $request->setUserResolver(function ($guard = null) use ($user) {
            return $user;
        });

        return $request;
    }
}
