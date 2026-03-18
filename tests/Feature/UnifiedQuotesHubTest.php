<?php

namespace Tests\Feature;

use App\Http\Controllers\UnifiedQuoteController;
use App\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UnifiedQuotesHubTest extends TestCase
{
    public function test_index_aborts_403_when_user_has_no_quote_permissions(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(__('messages.unauthorized'));

        $user = $this->makeStubUser([]);
        $request = Request::create('/quotes/hub', 'GET');
        $request->setUserResolver(static fn () => $user);
        $session = $this->app['session']->driver();
        $session->start();
        $session->put('user.business_id', 1);
        $request->setLaravelSession($session);

        app(UnifiedQuoteController::class)->index($request);
    }

    /**
     * @param  array<string, bool>  $abilities
     */
    protected function makeStubUser(array $abilities): User
    {
        return new class($abilities) extends User
        {
            public function __construct(private array $abilities)
            {
                parent::__construct();
                $this->id = 1;
            }

            public function can($ability, $arguments = [])
            {
                return $this->abilities[$ability] ?? false;
            }
        };
    }
}
