<?php

namespace Tests\Feature;

use App\Http\Controllers\CalendarController;
use App\User;
use App\Utils\CalendarEventUtil;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    public function test_ajax_index_returns_events_from_util()
    {
        $expected = [
            [
                'id' => 'schedule-1',
                'title' => 'Planning',
                'start' => '2026-03-17T09:00:00+07:00',
                'end' => '2026-03-17T10:00:00+07:00',
                'allDay' => false,
                'event_type' => 'schedule',
                'url' => null,
                'backgroundColor' => '#1B84FF',
                'borderColor' => '#1B84FF',
                'extendedProps' => ['type_label' => 'Schedule'],
            ],
        ];

        $user = $this->makeUser([
            'superadmin' => false,
        ]);

        $mock = \Mockery::mock(CalendarEventUtil::class);
        $mock->shouldReceive('buildFilters')
            ->once()
            ->with(\Mockery::type(Request::class), 9, $user)
            ->andReturn(['events' => ['schedule']]);
        $mock->shouldReceive('getEvents')
            ->once()
            ->with(['events' => ['schedule']], $user)
            ->andReturn($expected);

        $controller = new CalendarController($mock);
        $response = $controller->index(
            $this->makeRequest('/calendar', [
                'user.business_id' => 9,
            ], [
                'start' => '2026-03-01',
                'end' => '2026-03-31',
            ], $user, true)
        );

        $this->assertSame(200, $response->status());
        $this->assertSame($expected, $response->getData(true));
    }

    public function test_create_flow_throws_403_when_util_returns_no_payload()
    {
        $user = $this->makeUser([
            'superadmin' => false,
        ]);

        $mock = \Mockery::mock(CalendarEventUtil::class);
        $mock->shouldReceive('buildCreateFlowResponse')
            ->once()
            ->with('booking', 3, $user)
            ->andReturn(null);

        $controller = new CalendarController($mock);

        try {
            $controller->createFlow(
                $this->makeRequest('/calendar/create-flow', [
                    'user.business_id' => 3,
                ], [
                    'type' => 'booking',
                ], $user, false)
            );

            $this->fail('Expected a 403 HttpException.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    protected function makeUser(array $abilities)
    {
        return new class($abilities) extends User
        {
            protected array $abilities;

            public function __construct(array $abilities)
            {
                parent::__construct();
                $this->id = 7;
                $this->business_id = 1;
                $this->abilities = $abilities;
            }

            public function can($ability, $arguments = [])
            {
                return $this->abilities[$ability] ?? false;
            }
        };
    }

    protected function makeRequest(string $path, array $sessionData, array $query, User $user, bool $ajax): Request
    {
        $request = Request::create($path, 'GET', $query);
        $session = $this->app['session']->driver();
        $session->start();

        foreach ($sessionData as $key => $value) {
            $session->put($key, $value);
        }

        if ($ajax) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        $request->setLaravelSession($session);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $request;
    }
}
