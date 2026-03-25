<?php

namespace Tests\Unit;

use App\Contact;
use App\ContactFeed;
use App\Utils\ContactFeedProviderInterface;
use App\Utils\ContactFeedUtil;
use App\Utils\FacebookContactFeedProvider;
use App\Utils\GoogleContactFeedProvider;
use App\Utils\LinkedInContactFeedProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContactFeedUtilTest extends TestCase
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

        config()->set('services.contact_feeds.default_provider', 'google');
        config()->set('services.contact_feeds.providers', ['google', 'facebook', 'linkedin']);
        config()->set('services.google_custom_search.default_limit', 20);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contact_feeds');
        parent::tearDown();
    }

    public function test_canonicalize_url_removes_tracking_parameters_and_hash_is_stable(): void
    {
        $util = $this->makeUtilWithGoogleProvider(new StaticProvider([]));
        $canonical = $util->canonicalizeUrl('https://Example.com/news/a?utm_source=abc&b=1&gclid=x#top');

        $this->assertSame('https://example.com/news/a?b=1', $canonical);
        $this->assertSame(
            $util->hashUrl('google', $canonical),
            $util->hashUrl('google', 'https://example.com/news/a?b=1')
        );
    }

    public function test_load_feeds_skips_provider_when_records_already_exist(): void
    {
        $provider = new ThrowingProvider('Provider should not be called for existing records.');
        $util = $this->makeUtilWithGoogleProvider($provider);
        $contact = $this->makeContact(21);
        $hash = $util->hashUrl('google', 'https://example.com/existing');

        ContactFeed::create([
            'business_id' => 1,
            'contact_id' => $contact->id,
            'provider' => 'google',
            'title' => 'Existing story',
            'snippet' => 'already saved',
            'canonical_url' => 'https://example.com/existing',
            'url_hash' => $hash,
            'source_name' => 'example.com',
            'published_at' => now()->subDay(),
            'fetched_at' => now()->subHour(),
            'raw_payload' => ['existing' => true],
        ]);

        $result = $util->loadFeeds(1, $contact, ['provider' => 'google', 'limit' => 20]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['inserted_count']);
        $this->assertSame(1, $result['existing_count']);
        $this->assertStringContainsString('without searching again', strtolower($result['msg']));
    }

    public function test_update_feeds_inserts_only_new_records_and_skips_duplicates(): void
    {
        $provider_items = [
            [
                'provider' => 'google',
                'title' => 'Existing story',
                'snippet' => 'old',
                'canonical_url' => 'https://example.com/a?utm_source=test',
                'source_name' => 'example.com',
                'published_at' => now()->subDays(2)->toDateTimeString(),
                'raw_payload' => ['id' => 1],
            ],
            [
                'provider' => 'google',
                'title' => 'New story',
                'snippet' => 'new',
                'canonical_url' => 'https://example.com/b?utm_medium=social',
                'source_name' => 'example.com',
                'published_at' => now()->toDateTimeString(),
                'raw_payload' => ['id' => 2],
            ],
            [
                'provider' => 'google',
                'title' => 'New story duplicate in same payload',
                'snippet' => 'new duplicate',
                'canonical_url' => 'https://example.com/b?utm_campaign=xyz',
                'source_name' => 'example.com',
                'published_at' => now()->toDateTimeString(),
                'raw_payload' => ['id' => 3],
            ],
        ];

        $util = $this->makeUtilWithGoogleProvider(new StaticProvider($provider_items));
        $contact = $this->makeContact(30);
        $existing_canonical = $util->canonicalizeUrl('https://example.com/a');

        ContactFeed::create([
            'business_id' => 1,
            'contact_id' => $contact->id,
            'provider' => 'google',
            'title' => 'Existing story',
            'snippet' => 'old',
            'canonical_url' => $existing_canonical,
            'url_hash' => $util->hashUrl('google', $existing_canonical),
            'source_name' => 'example.com',
            'published_at' => now()->subDays(3),
            'fetched_at' => now()->subDays(3),
            'raw_payload' => ['existing' => true],
        ]);

        $result = $util->updateFeeds(1, $contact, ['provider' => 'google', 'limit' => 20]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['inserted_count']);
        $this->assertSame(2, $result['skipped_count']);
        $this->assertSame(2, ContactFeed::forContact(1, $contact->id)->where('provider', 'google')->count());
    }

    public function test_update_feeds_returns_safe_error_payload_when_provider_fails(): void
    {
        $util = $this->makeUtilWithGoogleProvider(new ThrowingProvider('Google API is unavailable.'));
        $contact = $this->makeContact(41);

        ContactFeed::create([
            'business_id' => 1,
            'contact_id' => $contact->id,
            'provider' => 'google',
            'title' => 'Existing story',
            'snippet' => 'old',
            'canonical_url' => 'https://example.com/existing',
            'url_hash' => $util->hashUrl('google', 'https://example.com/existing'),
            'source_name' => 'example.com',
            'published_at' => now()->subDay(),
            'fetched_at' => now()->subHour(),
            'raw_payload' => ['existing' => true],
        ]);

        $result = $util->updateFeeds(1, $contact, ['provider' => 'google']);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['inserted_count']);
        $this->assertSame(1, $result['existing_count']);
        $this->assertSame('Google API is unavailable.', $result['msg']);
    }

    public function test_normalize_provider_rejects_unknown_provider(): void
    {
        $util = $this->makeUtilWithGoogleProvider(new StaticProvider([]));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported provider selected.');

        $util->normalizeProvider('x');
    }

    public function test_placeholder_provider_returns_predictable_not_configured_response(): void
    {
        $util = $this->makeUtilWithGoogleProvider(new StaticProvider([]));
        $contact = $this->makeContact(52);

        $result = $util->updateFeeds(1, $contact, ['provider' => 'facebook', 'limit' => 20]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['inserted_count']);
        $this->assertSame(0, $result['existing_count']);
        $this->assertStringContainsString('not configured', strtolower($result['msg']));
    }

    public function test_google_query_always_keeps_contact_name_with_optional_keyword(): void
    {
        $provider = new ExposedGoogleContactFeedProvider();
        $contact = $this->makeContact(63);

        $query_with_keyword = $provider->exposeBuildQuery($contact, ['keyword' => 'just open a new branch']);
        $this->assertStringContainsString('"Acme Industries"', $query_with_keyword);
        $this->assertStringContainsString('"just open a new branch"', $query_with_keyword);
        $this->assertStringContainsString('latest news', strtolower($query_with_keyword));

        $query_with_custom = $provider->exposeBuildQuery($contact, ['query' => 'raw steel forecast']);
        $this->assertStringContainsString('"Acme Industries"', $query_with_custom);
        $this->assertStringContainsString('"raw steel forecast"', $query_with_custom);
    }

    protected function makeUtilWithGoogleProvider(ContactFeedProviderInterface $provider): ContactFeedUtil
    {
        $util = new ContactFeedUtil(
            new GoogleContactFeedProvider(),
            new FacebookContactFeedProvider(),
            new LinkedInContactFeedProvider()
        );
        $util->setProvider('google', $provider);

        return $util;
    }

    protected function makeContact(int $id): Contact
    {
        $contact = new Contact();
        $contact->id = $id;
        $contact->name = 'Acme Contact';
        $contact->supplier_business_name = 'Acme Industries';
        $contact->city = 'Bangkok';
        $contact->state = 'Bangkok';
        $contact->country = 'Thailand';

        return $contact;
    }
}

class StaticProvider implements ContactFeedProviderInterface
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function search(Contact $contact, array $options = [])
    {
        return $this->items;
    }
}

class ThrowingProvider implements ContactFeedProviderInterface
{
    protected $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function search(Contact $contact, array $options = [])
    {
        throw new \RuntimeException($this->message);
    }
}

class ExposedGoogleContactFeedProvider extends GoogleContactFeedProvider
{
    public function exposeBuildQuery(Contact $contact, array $options = [])
    {
        return $this->buildQuery($contact, $options);
    }
}
