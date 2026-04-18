<?php

namespace Tests\Unit;

use App\Http\Controllers\ManageUserController;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\TwoFactorUtil;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class ManageUserControllerSessionSyncTest extends TestCase
{
    public function test_it_refreshes_the_current_session_user_after_self_edit(): void
    {
        $request = Request::create('/users/7', 'PUT');
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('user', [
            'id' => 7,
            'surname' => 'Mr',
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.com',
            'business_id' => 11,
            'language' => 'en',
        ]);
        $request->setLaravelSession($session);

        $user = new User;
        $user->id = 7;
        $user->surname = 'Ms';
        $user->first_name = 'New';
        $user->last_name = 'Person';
        $user->email = 'new@example.com';
        $user->business_id = 11;
        $user->language = 'vi';

        $this->invokeSessionSync($request, $user);

        $this->assertSame('vi', $request->session()->get('user.language'));
        $this->assertSame('new@example.com', $request->session()->get('user.email'));
        $this->assertSame('New', $request->session()->get('user.first_name'));
    }

    public function test_it_leaves_the_current_session_unchanged_when_editing_another_user(): void
    {
        $request = Request::create('/users/9', 'PUT');
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('user', [
            'id' => 7,
            'surname' => 'Mr',
            'first_name' => 'Current',
            'last_name' => 'Admin',
            'email' => 'current@example.com',
            'business_id' => 11,
            'language' => 'en',
        ]);
        $request->setLaravelSession($session);

        $user = new User;
        $user->id = 9;
        $user->surname = 'Ms';
        $user->first_name = 'Other';
        $user->last_name = 'User';
        $user->email = 'other@example.com';
        $user->business_id = 11;
        $user->language = 'vi';

        $this->invokeSessionSync($request, $user);

        $this->assertSame('en', $request->session()->get('user.language'));
        $this->assertSame('current@example.com', $request->session()->get('user.email'));
        $this->assertSame('Current', $request->session()->get('user.first_name'));
    }

    private function invokeSessionSync(Request $request, User $user): void
    {
        $controller = new ManageUserController(
            $this->createMock(ModuleUtil::class),
            $this->createMock(TwoFactorUtil::class)
        );
        $method = new ReflectionMethod($controller, 'syncCurrentUserSession');
        $method->setAccessible(true);
        $method->invoke($controller, $request, $user);
    }
}
