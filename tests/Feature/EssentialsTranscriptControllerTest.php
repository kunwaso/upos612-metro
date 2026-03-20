<?php

namespace Tests\Feature;

use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Essentials\Http\Controllers\TranscriptController;
use Modules\Essentials\Http\Requests\PreviewTranscriptRequest;
use Modules\Essentials\Http\Requests\StoreTranscriptRequest;
use Modules\Essentials\Http\Requests\TranslateTranscriptRequest;
use Modules\Essentials\Utils\TranscriptTranslationUtil;
use Modules\Essentials\Utils\TranscriptUtil;
use Tests\TestCase;

class EssentialsTranscriptControllerTest extends TestCase
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

        Schema::dropIfExists('essentials_transcripts');
        Schema::create('essentials_transcripts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('user_id')->index();
            $table->string('title')->nullable();
            $table->string('source_language', 20)->nullable();
            $table->string('target_language', 20)->nullable();
            $table->longText('transcript');
            $table->longText('translated_text')->nullable();
            $table->string('audio_filename')->nullable();
            $table->enum('source', ['upload', 'live'])->default('upload');
            $table->timestamps();
        });

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            [
                'id' => 7,
                'surname' => 'Mr',
                'first_name' => 'Ray',
                'last_name' => 'Tester',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'surname' => 'Ms',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_preview_returns_payload_without_creating_database_row()
    {
        Storage::fake(config('filesystems.default', 'local'));

        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $translationUtil = \Mockery::mock(TranscriptTranslationUtil::class);

        $transcriptUtil->shouldReceive('getApiKey')
            ->once()
            ->with(44)
            ->andReturn('groq-test-key');
        $transcriptUtil->shouldReceive('resolveTranscriptionLanguage')
            ->once()
            ->with('ce')
            ->andReturn('zh');
        $transcriptUtil->shouldReceive('transcribe')
            ->once()
            ->andReturn('你好');
        $translationUtil->shouldReceive('translateText')
            ->once()
            ->with(44, 7, '你好', 'ce', 'vi')
            ->andReturn('Xin chao');

        $controller = new TranscriptController($moduleUtil, $transcriptUtil, $translationUtil);
        $user = $this->makeUser(true);
        $this->be($user);

        $request = PreviewTranscriptRequest::create(
            '/essentials/transcripts/preview',
            'POST',
            [
                'source_language' => 'ce',
                'target_language' => 'vi',
            ],
            [],
            [
                'recorded_audio' => UploadedFile::fake()->create('recording.webm', 64, 'audio/webm'),
            ]
        );
        $request->setLaravelSession($this->makeSession([
            'user.business_id' => 44,
            'user.id' => 7,
        ]));
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $controller->preview($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame('你好', data_get($payload, 'data.transcript_text'));
        $this->assertSame('Xin chao', data_get($payload, 'data.translated_text'));
        $this->assertSame(0, DB::table('essentials_transcripts')->count());
    }

    public function test_store_persists_audio_transcript_translation_and_language_pair()
    {
        Storage::fake(config('filesystems.default', 'local'));

        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $translationUtil = \Mockery::mock(TranscriptTranslationUtil::class);

        $controller = new TranscriptController($moduleUtil, $transcriptUtil, $translationUtil);
        $user = $this->makeUser(true);
        $this->be($user);

        $request = StoreTranscriptRequest::create(
            '/essentials/transcripts',
            'POST',
            [
                'title' => 'Client sync',
                'source' => 'live',
                'source_language' => 'ce',
                'target_language' => 'vi',
                'transcript_text' => '你好',
                'translated_text' => 'Xin chao',
            ],
            [],
            [
                'recorded_audio' => UploadedFile::fake()->create('recording.webm', 64, 'audio/webm'),
            ]
        );
        $request->setLaravelSession($this->makeSession([
            'user.business_id' => 44,
            'user.id' => 7,
        ]));
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $controller->store($request);
        $payload = $response->getData(true);

        $this->assertTrue($payload['success']);
        $this->assertSame(1, DB::table('essentials_transcripts')->count());

        $row = DB::table('essentials_transcripts')->first();
        $this->assertSame('Client sync', $row->title);
        $this->assertSame('live', $row->source);
        $this->assertSame('ce', $row->source_language);
        $this->assertSame('vi', $row->target_language);
        $this->assertSame('你好', $row->transcript);
        $this->assertSame('Xin chao', $row->translated_text);
        $this->assertNotEmpty($row->audio_filename);
    }

    public function test_translate_returns_controlled_error_message_when_translation_fails()
    {
        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $translationUtil = \Mockery::mock(TranscriptTranslationUtil::class);

        $translationUtil->shouldReceive('translateText')
            ->once()
            ->with(44, 7, 'Hello world', 'en', 'vi')
            ->andThrow(new \RuntimeException('Provider key missing'));

        $controller = new TranscriptController($moduleUtil, $transcriptUtil, $translationUtil);
        $user = $this->makeUser(true);
        $this->be($user);

        $request = TranslateTranscriptRequest::create(
            '/essentials/transcripts/translate',
            'POST',
            [
                'text' => 'Hello world',
                'source_language' => 'en',
                'target_language' => 'vi',
            ]
        );
        $request->setLaravelSession($this->makeSession([
            'user.business_id' => 44,
            'user.id' => 7,
        ]));
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $controller->translate($request);
        $payload = $response->getData(true);

        $this->assertFalse($payload['success']);
        $this->assertSame('Provider key missing', $payload['msg']);
    }

    public function test_index_ajax_returns_language_pair_and_translated_preview_columns()
    {
        DB::table('essentials_transcripts')->insert([
            'business_id' => 44,
            'user_id' => 7,
            'title' => 'Live call',
            'source' => 'live',
            'source_language' => 'ce',
            'target_language' => 'vi',
            'transcript' => '你好',
            'translated_text' => 'Xin chao',
            'audio_filename' => 'essentials_audio/sample.webm',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $translationUtil = \Mockery::mock(TranscriptTranslationUtil::class);

        $transcriptUtil->shouldReceive('getLanguageOptions')
            ->once()
            ->andReturn([
                'ce' => 'Chinese',
                'vi' => 'Vietnamese',
            ]);
        $transcriptUtil->shouldReceive('getLanguageLabel')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($lang) {
                return $lang === 'ce' ? 'Chinese' : 'Vietnamese';
            });

        $controller = new TranscriptController($moduleUtil, $transcriptUtil, $translationUtil);
        $user = $this->makeUser(true);
        $this->be($user);

        $request = \Illuminate\Http\Request::create('/essentials/transcripts', 'GET');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setLaravelSession($this->makeSession([
            'user.business_id' => 44,
            'user.id' => 7,
        ]));
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $controller->index($request);
        $payload = $response->getData(true);
        $row = $payload['data'][0];

        $this->assertArrayHasKey('language_pair', $row);
        $this->assertArrayHasKey('translated_preview', $row);
        $languagePairText = html_entity_decode(strip_tags($row['language_pair']));
        $this->assertStringContainsString('Chinese -> Vietnamese', $languagePairText);
        $this->assertStringContainsString('Xin chao', $row['translated_preview']);
    }

    public function test_index_ajax_global_search_supports_title_text_user_language_and_date_tokens()
    {
        DB::table('essentials_transcripts')->insert([
            [
                'id' => 11,
                'business_id' => 44,
                'user_id' => 7,
                'title' => 'Client sync',
                'source' => 'live',
                'source_language' => 'ce',
                'target_language' => 'vi',
                'transcript' => '你好 from customer',
                'translated_text' => 'Xin chao from customer',
                'audio_filename' => 'essentials_audio/one.webm',
                'created_at' => '2026-03-20 09:15:00',
                'updated_at' => '2026-03-20 09:15:00',
            ],
            [
                'id' => 12,
                'business_id' => 44,
                'user_id' => 8,
                'title' => 'Weekly review',
                'source' => 'upload',
                'source_language' => 'fr',
                'target_language' => 'en',
                'transcript' => 'Bonjour from supplier',
                'translated_text' => 'Hello from supplier',
                'audio_filename' => 'essentials_audio/two.webm',
                'created_at' => '2026-03-21 14:10:00',
                'updated_at' => '2026-03-21 14:10:00',
            ],
        ]);

        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $translationUtil = \Mockery::mock(TranscriptTranslationUtil::class);

        $transcriptUtil->shouldReceive('getLanguageOptions')
            ->zeroOrMoreTimes()
            ->andReturn([
                'en' => 'English',
                'fr' => 'French',
                'ce' => 'Chinese',
                'vi' => 'Vietnamese',
            ]);
        $transcriptUtil->shouldReceive('getLanguageLabel')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($lang) {
                $labels = [
                    'en' => 'English',
                    'fr' => 'French',
                    'ce' => 'Chinese',
                    'vi' => 'Vietnamese',
                ];

                return $labels[$lang] ?? strtoupper((string) $lang);
            });

        $controller = new TranscriptController($moduleUtil, $transcriptUtil, $translationUtil);
        $user = $this->makeUser(true);
        $this->be($user);

        $searchCases = [
            ['Client sync', [11], [12]],
            ['你好', [11], [12]],
            ['Xin chao', [11], [12]],
            ['Ray Tester', [11], [12]],
            ['Chinese', [11], [12]],
            ['ce -> vi', [11], [12]],
            ['20 Mar 2026', [11], [12]],
            ['2026-03-20', [11], [12]],
        ];

        foreach ($searchCases as [$term, $mustInclude, $mustExclude]) {
            $request = $this->makeDataTablesAjaxRequest($this->makeDataTablesPayload($term), $user);
            $response = $controller->index($request);
            $payload = $response->getData(true);
            $rowIds = array_map('intval', array_column($payload['data'], 'id'));

            foreach ($mustInclude as $expectedId) {
                $this->assertContains($expectedId, $rowIds, 'Expected ID ' . $expectedId . ' for search [' . $term . ']');
            }

            foreach ($mustExclude as $unexpectedId) {
                $this->assertNotContains($unexpectedId, $rowIds, 'Unexpected ID ' . $unexpectedId . ' for search [' . $term . ']');
            }
        }
    }

    public function test_index_ajax_ordering_works_for_title_and_created_at_columns()
    {
        DB::table('essentials_transcripts')->insert([
            [
                'id' => 21,
                'business_id' => 44,
                'user_id' => 7,
                'title' => 'Bravo title',
                'source' => 'live',
                'source_language' => 'ce',
                'target_language' => 'vi',
                'transcript' => 'Line B',
                'translated_text' => 'Dong B',
                'audio_filename' => 'essentials_audio/order-one.webm',
                'created_at' => '2026-03-20 09:15:00',
                'updated_at' => '2026-03-20 09:15:00',
            ],
            [
                'id' => 22,
                'business_id' => 44,
                'user_id' => 8,
                'title' => 'Alpha title',
                'source' => 'upload',
                'source_language' => 'en',
                'target_language' => 'vi',
                'transcript' => 'Line A',
                'translated_text' => 'Dong A',
                'audio_filename' => 'essentials_audio/order-two.webm',
                'created_at' => '2026-03-21 14:10:00',
                'updated_at' => '2026-03-21 14:10:00',
            ],
        ]);

        $moduleUtil = \Mockery::mock(ModuleUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $translationUtil = \Mockery::mock(TranscriptTranslationUtil::class);

        $transcriptUtil->shouldReceive('getLanguageOptions')
            ->zeroOrMoreTimes()
            ->andReturn([
                'en' => 'English',
                'ce' => 'Chinese',
                'vi' => 'Vietnamese',
            ]);
        $transcriptUtil->shouldReceive('getLanguageLabel')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($lang) {
                $labels = [
                    'en' => 'English',
                    'ce' => 'Chinese',
                    'vi' => 'Vietnamese',
                ];

                return $labels[$lang] ?? strtoupper((string) $lang);
            });

        $controller = new TranscriptController($moduleUtil, $transcriptUtil, $translationUtil);
        $user = $this->makeUser(true);
        $this->be($user);

        $titleOrderRequest = $this->makeDataTablesAjaxRequest($this->makeDataTablesPayload('', 0, 'asc'), $user);
        $titleOrderPayload = $controller->index($titleOrderRequest)->getData(true);
        $this->assertSame('Alpha title', data_get($titleOrderPayload, 'data.0.title'));

        $dateOrderRequest = $this->makeDataTablesAjaxRequest($this->makeDataTablesPayload('', 4, 'desc'), $user);
        $dateOrderPayload = $controller->index($dateOrderRequest)->getData(true);
        $this->assertSame('Alpha title', data_get($dateOrderPayload, 'data.0.title'));
    }

    protected function makeUser(bool $isSuperadmin): User
    {
        return new class($isSuperadmin) extends User
        {
            protected bool $isSuperadmin;

            public function __construct(bool $isSuperadmin)
            {
                parent::__construct();
                $this->id = 7;
                $this->business_id = 44;
                $this->isSuperadmin = $isSuperadmin;
            }

            public function can($ability, $arguments = [])
            {
                if ($ability === 'superadmin') {
                    return $this->isSuperadmin;
                }

                return false;
            }
        };
    }

    protected function makeSession(array $data)
    {
        $session = $this->app['session']->driver();
        $session->start();

        foreach ($data as $key => $value) {
            $session->put($key, $value);
        }

        return $session;
    }

    protected function makeDataTablesPayload(string $searchValue = '', int $orderColumn = 4, string $orderDirection = 'desc'): array
    {
        return [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => [
                'value' => $searchValue,
                'regex' => 'false',
            ],
            'order' => [
                [
                    'column' => (string) $orderColumn,
                    'dir' => $orderDirection,
                ],
            ],
            'columns' => [
                [
                    'data' => 'title',
                    'name' => 'essentials_transcripts.title',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
                [
                    'data' => 'source',
                    'name' => 'essentials_transcripts.source',
                    'searchable' => 'false',
                    'orderable' => 'false',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
                [
                    'data' => 'language_pair',
                    'name' => 'language_pair',
                    'searchable' => 'true',
                    'orderable' => 'false',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
                [
                    'data' => 'user_name',
                    'name' => 'user_name',
                    'searchable' => 'true',
                    'orderable' => 'false',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
                [
                    'data' => 'created_at',
                    'name' => 'essentials_transcripts.created_at',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
                [
                    'data' => 'transcript',
                    'name' => 'essentials_transcripts.transcript',
                    'searchable' => 'true',
                    'orderable' => 'false',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
                [
                    'data' => 'translated_preview',
                    'name' => 'essentials_transcripts.translated_text',
                    'searchable' => 'true',
                    'orderable' => 'false',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
                [
                    'data' => 'action',
                    'name' => 'action',
                    'searchable' => 'false',
                    'orderable' => 'false',
                    'search' => ['value' => '', 'regex' => 'false'],
                ],
            ],
        ];
    }

    protected function makeDataTablesAjaxRequest(array $payload, User $user): \Illuminate\Http\Request
    {
        $request = \Illuminate\Http\Request::create('/essentials/transcripts', 'GET', $payload);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setLaravelSession($this->makeSession([
            'user.business_id' => 44,
            'user.id' => 7,
        ]));
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $this->app->instance('request', $request);

        return $request;
    }
}
