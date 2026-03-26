<?php

namespace Tests\Unit;

use App\Contact;
use App\ContactFeed;
use App\Utils\ContactFeedProviderInterface;
use App\Utils\ContactFeedUtil;
use App\Utils\FacebookContactFeedProvider;
use App\Utils\GoogleContactFeedProvider;
use App\Utils\LinkedInContactFeedProvider;
use App\Utils\SerpApiGoogleContactFeedProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            $table->text('image_url')->nullable();
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
        config()->set('services.contact_feeds.providers', ['google']);
        config()->set('services.google_custom_search.default_limit', 30);
        config()->set('services.serpapi_google.fallback_enabled', true);
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
            'image_url' => 'https://images.example.com/existing.jpg',
            'canonical_url' => 'https://example.com/existing',
            'url_hash' => $hash,
            'source_name' => 'example.com',
            'published_at' => now()->subDay(),
            'fetched_at' => now()->subHour(),
            'raw_payload' => ['existing' => true],
        ]);

        $result = $util->loadFeeds(1, $contact, ['provider' => 'google', 'limit' => 30]);

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
                'image_url' => 'https://images.example.com/a.jpg',
                'canonical_url' => 'https://example.com/a?utm_source=test',
                'source_name' => 'example.com',
                'published_at' => now()->subDays(2)->toDateTimeString(),
                'raw_payload' => ['id' => 1],
            ],
            [
                'provider' => 'google',
                'title' => 'New story',
                'snippet' => 'new',
                'image_url' => 'https://images.example.com/b.jpg',
                'canonical_url' => 'https://example.com/b?utm_medium=social',
                'source_name' => 'example.com',
                'published_at' => now()->toDateTimeString(),
                'raw_payload' => ['id' => 2],
            ],
            [
                'provider' => 'google',
                'title' => 'New story duplicate in same payload',
                'snippet' => 'new duplicate',
                'image_url' => 'https://images.example.com/b-alt.jpg',
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
            'image_url' => 'https://images.example.com/existing.jpg',
            'canonical_url' => $existing_canonical,
            'url_hash' => $util->hashUrl('google', $existing_canonical),
            'source_name' => 'example.com',
            'published_at' => now()->subDays(3),
            'fetched_at' => now()->subDays(3),
            'raw_payload' => ['existing' => true],
        ]);

        $result = $util->updateFeeds(1, $contact, ['provider' => 'google', 'limit' => 30]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['inserted_count']);
        $this->assertSame(2, $result['skipped_count']);
        $this->assertSame(2, ContactFeed::forContact(1, $contact->id)->where('provider', 'google')->count());
        $this->assertSame(
            'https://images.example.com/b.jpg',
            ContactFeed::forContact(1, $contact->id)->where('title', 'New story')->value('image_url')
        );
    }

    public function test_update_feeds_skips_items_with_older_or_equal_published_dates(): void
    {
        $provider_items = [
            [
                'provider' => 'google',
                'title' => 'Old story',
                'snippet' => 'old',
                'canonical_url' => 'https://example.com/old-story',
                'source_name' => 'example.com',
                'published_at' => now()->subDays(2)->toDateTimeString(),
                'raw_payload' => ['id' => 'old'],
            ],
            [
                'provider' => 'google',
                'title' => 'Same day story',
                'snippet' => 'same',
                'canonical_url' => 'https://example.com/same-story',
                'source_name' => 'example.com',
                'published_at' => now()->subDay()->toDateTimeString(),
                'raw_payload' => ['id' => 'same'],
            ],
            [
                'provider' => 'google',
                'title' => 'Undated but new',
                'snippet' => 'undated',
                'canonical_url' => 'https://example.com/undated-story',
                'source_name' => 'example.com',
                'published_at' => null,
                'raw_payload' => ['id' => 'undated'],
            ],
            [
                'provider' => 'google',
                'title' => 'Fresh story',
                'snippet' => 'fresh',
                'canonical_url' => 'https://example.com/fresh-story',
                'source_name' => 'example.com',
                'published_at' => now()->toDateTimeString(),
                'raw_payload' => ['id' => 'fresh'],
            ],
        ];
        $util = $this->makeUtilWithGoogleProvider(new StaticProvider($provider_items));
        $contact = $this->makeContact(52);

        ContactFeed::create([
            'business_id' => 1,
            'contact_id' => $contact->id,
            'provider' => 'google',
            'title' => 'Existing latest',
            'snippet' => 'existing',
            'image_url' => null,
            'canonical_url' => 'https://example.com/existing-latest',
            'url_hash' => $util->hashUrl('google', 'https://example.com/existing-latest'),
            'source_name' => 'example.com',
            'published_at' => now()->subDay(),
            'fetched_at' => now()->subHour(),
            'raw_payload' => ['existing' => true],
        ]);

        $result = $util->updateFeeds(1, $contact, ['provider' => 'google', 'limit' => 30]);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['inserted_count']);
        $this->assertSame(2, $result['skipped_count']);
        $this->assertDatabaseHas('contact_feeds', ['title' => 'Fresh story']);
        $this->assertDatabaseHas('contact_feeds', ['title' => 'Undated but new']);
        $this->assertDatabaseMissing('contact_feeds', ['title' => 'Old story']);
        $this->assertDatabaseMissing('contact_feeds', ['title' => 'Same day story']);
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
            'image_url' => null,
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

        $util->normalizeProvider('facebook');
    }

    public function test_google_provider_paginates_three_pages_and_extracts_image_url(): void
    {
        config()->set('services.google_custom_search.api_key', 'test-key');
        config()->set('services.google_custom_search.search_engine_id', 'test-cx');

        $starts = [];
        Http::fake(function ($request) use (&$starts) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $starts[] = (int) ($query['start'] ?? 1);

            return Http::response([
                'items' => [[
                    'title' => 'Story '.$query['start'],
                    'link' => 'https://example.com/story-'.$query['start'],
                    'snippet' => 'Story snippet '.$query['start'],
                    'displayLink' => 'example.com',
                    'pagemap' => [
                        'cse_image' => [
                            ['src' => 'https://images.example.com/story-'.$query['start'].'.jpg'],
                        ],
                        'metatags' => [[
                            'article:published_time' => now()->toIso8601String(),
                        ]],
                    ],
                ]],
            ]);
        });

        $provider = new GoogleContactFeedProvider();
        $contact = $this->makeContact(63);
        $items = $provider->search($contact, ['limit' => 30, 'published_after' => now()->subDays(2)->toDateTimeString()]);

        $this->assertCount(3, $items);
        $this->assertSame([1, 11, 21], $starts);
        $this->assertSame('https://images.example.com/story-1.jpg', $items[0]['image_url']);
    }

    public function test_google_query_always_keeps_contact_name_and_latest_news_phrase(): void
    {
        $provider = new ExposedGoogleContactFeedProvider();
        $contact = $this->makeContact(64);

        $query = $provider->exposeBuildQuery($contact);

        $this->assertStringContainsString('"Acme Industries"', $query);
        $this->assertStringContainsString('latest news', strtolower($query));
    }

    public function test_google_date_restrict_is_built_from_published_after(): void
    {
        $provider = new ExposedGoogleContactFeedProvider();

        $this->assertSame('d8', $provider->exposeBuildDateRestrict(now()->subDays(7)->toDateTimeString()));
    }

    public function test_google_provider_falls_back_to_serpapi_when_google_access_is_forbidden(): void
    {
        config()->set('services.google_custom_search.api_key', 'google-key');
        config()->set('services.google_custom_search.search_engine_id', 'google-cx');
        config()->set('services.serpapi_google.api_key', 'serp-key');

        Http::fake([
            'https://www.googleapis.com/customsearch/v1*' => Http::response([
                'error' => [
                    'message' => 'This project does not have the access to Custom Search JSON API.',
                    'errors' => [
                        ['reason' => 'forbidden'],
                    ],
                ],
            ], 403),
            'https://serpapi.com/search*' => Http::response([
                'news_results' => [[
                    'title' => 'Fallback story',
                    'link' => 'https://example.com/fallback-story',
                    'snippet' => 'Fetched from fallback provider.',
                    'thumbnail' => 'https://images.example.com/fallback.jpg',
                    'source' => 'Example News',
                    'date' => now()->toIso8601String(),
                ]],
            ], 200),
        ]);

        $provider = new GoogleContactFeedProvider(new SerpApiGoogleContactFeedProvider());
        $contact = $this->makeContact(65);
        $items = $provider->search($contact, ['limit' => 10]);

        $this->assertCount(1, $items);
        $this->assertSame('Fallback story', $items[0]['title']);
        $this->assertSame('https://images.example.com/fallback.jpg', $items[0]['image_url']);
        $this->assertSame('google', $items[0]['provider']);
        $this->assertSame('serpapi_google', $items[0]['raw_payload']['_feed_source']);
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

    public function exposeBuildDateRestrict($published_after)
    {
        return $this->buildDateRestrict($published_after);
    }
}
