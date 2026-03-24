<?php

namespace Tests\Feature;

use App\Http\Controllers\ContactController;
use App\Http\Requests\ContactFeedSyncRequest;
use App\User;
use App\Utils\ContactFeedUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ContactFeedsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->integer('created_by')->nullable();
            $table->string('type', 20)->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('contact_feeds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->integer('contact_id')->index();
            $table->string('provider', 50)->index();
            $table->string('title', 512);
            $table->text('snippet')->nullable();
            $table->text('canonical_url');
            $table->string('url_hash', 64)->index();
            $table->string('source_name')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('fetched_at')->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'contact_id', 'provider', 'url_hash'], 'contact_feeds_business_contact_provider_hash_unique');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contact_feeds');
        Schema::dropIfExists('contacts');
        parent::tearDown();
    }

    public function test_load_contact_feeds_returns_403_for_unauthorized_user(): void
    {
        $user = $this->makeUser([
            'supplier.view' => false,
            'supplier.view_own' => false,
            'customer.view' => false,
            'customer.view_own' => false,
        ]);
        $this->be($user);

        $feed_util = \Mockery::mock(ContactFeedUtil::class);
        $feed_util->shouldNotReceive('loadFeeds');
        $controller = $this->makeController($feed_util);
        $request = $this->makeFeedRequest('/contacts/99/feeds/load', 'POST', [
            'provider' => 'google',
            'limit' => 20,
        ], ['user.business_id' => 1]);

        try {
            $controller->loadContactFeeds($request, 99);
            $this->fail('Expected 403 for unauthorized user.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_load_contact_feeds_blocks_cross_tenant_contact_access(): void
    {
        $user = $this->makeUser([
            'supplier.view' => true,
            'supplier.view_own' => false,
            'customer.view' => false,
            'customer.view_own' => false,
        ]);
        $this->be($user);

        DB::table('contacts')->insert([
            'id' => 15,
            'business_id' => 2,
            'created_by' => 7,
            'type' => 'supplier',
            'name' => 'Tenant B Contact',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $feed_util = \Mockery::mock(ContactFeedUtil::class);
        $feed_util->shouldNotReceive('loadFeeds');
        $controller = $this->makeController($feed_util);
        $request = $this->makeFeedRequest('/contacts/15/feeds/load', 'POST', [
            'provider' => 'google',
            'limit' => 20,
        ], ['user.business_id' => 1]);

        $this->expectException(NotFoundHttpException::class);
        $controller->loadContactFeeds($request, 15);
    }

    public function test_feed_sync_request_rejects_invalid_provider_and_limit(): void
    {
        $user = $this->makeUser([
            'supplier.view' => true,
            'supplier.view_own' => false,
            'customer.view' => false,
            'customer.view_own' => false,
        ]);
        $this->be($user);

        $this->expectException(ValidationException::class);
        $this->makeFeedRequest('/contacts/1/feeds/load', 'POST', [
            'provider' => 'x',
            'limit' => 99,
        ], ['user.business_id' => 1]);
    }

    public function test_load_contact_feeds_returns_safe_payload_when_provider_throws(): void
    {
        $user = $this->makeUser([
            'supplier.view' => true,
            'supplier.view_own' => false,
            'customer.view' => false,
            'customer.view_own' => false,
        ]);
        $this->be($user);

        DB::table('contacts')->insert([
            'id' => 21,
            'business_id' => 1,
            'created_by' => 7,
            'type' => 'supplier',
            'name' => 'Acme Contact',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contact_feeds')->insert([
            'business_id' => 1,
            'contact_id' => 21,
            'provider' => 'google',
            'title' => 'Existing story',
            'snippet' => 'existing',
            'canonical_url' => 'https://example.com/existing',
            'url_hash' => hash('sha256', 'google|https://example.com/existing'),
            'source_name' => 'example.com',
            'published_at' => now()->subDay(),
            'fetched_at' => now()->subHour(),
            'raw_payload' => json_encode(['existing' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $feed_util = \Mockery::mock(ContactFeedUtil::class);
        $feed_util->shouldReceive('normalizeProvider')
            ->once()
            ->with('google')
            ->andReturn('google');
        $feed_util->shouldReceive('loadFeeds')
            ->once()
            ->andThrow(new \RuntimeException('Provider unavailable'));
        $controller = $this->makeController($feed_util);
        $request = $this->makeFeedRequest('/contacts/21/feeds/load', 'POST', [
            'provider' => 'google',
            'limit' => 20,
        ], ['user.business_id' => 1]);

        $result = $controller->loadContactFeeds($request, 21);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['inserted_count']);
        $this->assertSame(1, $result['existing_count']);
        $this->assertSame('google', $result['provider']);
    }

    protected function makeController(ContactFeedUtil $feed_util): ContactController
    {
        return new ContactController(
            \Mockery::mock(Util::class),
            \Mockery::mock(ModuleUtil::class),
            \Mockery::mock(TransactionUtil::class),
            \Mockery::mock(NotificationUtil::class),
            \Mockery::mock(ContactUtil::class),
            $feed_util
        );
    }

    protected function makeFeedRequest(string $path, string $method, array $payload, array $session_data): ContactFeedSyncRequest
    {
        $request = ContactFeedSyncRequest::create($path, $method, $payload);
        $session = $this->app['session']->driver();
        $session->start();

        foreach ($session_data as $key => $value) {
            $session->put($key, $value);
        }

        $request->setLaravelSession($session);
        $request->setUserResolver(function () {
            return auth()->user();
        });
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->validateResolved();

        return $request;
    }

    protected function makeUser(array $abilities): User
    {
        return new class($abilities) extends User
        {
            protected $abilities;

            public function __construct(array $abilities)
            {
                parent::__construct();
                $this->id = 7;
                $this->business_id = 1;
                $this->selected_contacts = 0;
                $this->abilities = $abilities;
            }

            public function can($ability, $arguments = [])
            {
                return $this->abilities[$ability] ?? false;
            }
        };
    }
}
