<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserControllerLanguageSessionRefreshTest extends TestCase
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
            $table->string('surname');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->char('language', 7)->default('en');
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

    public function test_update_profile_refreshes_the_session_language_for_self_edit(): void
    {
        $user = User::create_user([
            'surname' => 'Mr',
            'first_name' => 'Ray',
            'last_name' => 'Tester',
            'username' => 'ray.tester',
            'email' => 'ray@example.com',
            'password' => 'secret',
            'language' => 'en',
        ]);

        $moduleUtil = $this->createMock(ModuleUtil::class);
        $moduleUtil->expects($this->once())
            ->method('notAllowedInDemo')
            ->willReturn(null);

        $controller = new UserController($moduleUtil);

        $request = Request::create('/user/profile', 'POST', [
            'surname' => 'Mr',
            'first_name' => 'Ray',
            'last_name' => 'Tester',
            'email' => 'ray@example.com',
            'language' => 'vi',
        ]);

        $session = $this->app['session']->driver();
        $session->start();
        $session->put('user.id', $user->id);
        $session->put('user.business_id', 15);

        $request->setLaravelSession($session);
        $request->setUserResolver(static function () {
            return null;
        });
        $this->app->instance('request', $request);

        $response = $controller->updateProfile($request);

        $this->assertSame(302, $response->status());
        $this->assertSame('vi', $request->session()->get('user')['language']);
        $this->assertSame(15, $request->session()->get('user')['business_id']);
        $this->assertSame('vi', User::findOrFail($user->id)->language);
    }
}
