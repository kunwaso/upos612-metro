<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Modules\Aichat\Entities\ChatCredential;
use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatUtil;
use Modules\Essentials\Utils\PyGoogleTranslateClient;
use Modules\Essentials\Utils\TranscriptTranslationUtil;
use Modules\Essentials\Utils\TranscriptUtil;
use Tests\TestCase;

class TranscriptTranslationUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        Cache::flush();
    }

    public function test_returns_original_text_when_language_pair_is_the_same()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $pyGoogleTranslateClient = \Mockery::mock(PyGoogleTranslateClient::class);

        $chatUtil->shouldNotReceive('buildModelOptions');
        $aiChatUtil->shouldNotReceive('generateText');
        $transcriptUtil->shouldNotReceive('getTranslationEngine');
        $pyGoogleTranslateClient->shouldNotReceive('translate');

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil, $pyGoogleTranslateClient);
        $result = $util->translateText(9, 3, 'Xin chao', 'vi', 'vi');

        $this->assertSame('Xin chao', $result);
    }

    public function test_routes_to_py_googletrans_and_uses_cache()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $pyGoogleTranslateClient = \Mockery::mock(PyGoogleTranslateClient::class);

        $chatUtil->shouldNotReceive('buildModelOptions');
        $aiChatUtil->shouldNotReceive('generateText');
        $transcriptUtil->shouldReceive('getTranslationEngine')
            ->twice()
            ->with(9)
            ->andReturn('py_googletrans');
        $transcriptUtil->shouldReceive('resolvePyTranslationLanguage')
            ->times(4)
            ->andReturnUsing(function ($language) {
                return strtolower((string) $language);
            });
        $transcriptUtil->shouldReceive('buildTranslationCacheKey')
            ->twice()
            ->with('py_googletrans', 'en', 'vi', 'Hello world')
            ->andReturn('essentials:transcripts:translate:py_googletrans:en:vi:test');
        $transcriptUtil->shouldReceive('getTranslationCacheTtlSeconds')
            ->twice()
            ->with(9)
            ->andReturn(600);
        $transcriptUtil->shouldReceive('getTranslationPythonServiceUrls')
            ->twice()
            ->with(9)
            ->andReturn(['https://translate.googleapis.com']);
        $pyGoogleTranslateClient->shouldReceive('translate')
            ->once()
            ->with(9, 'Hello world', 'en', 'vi', ['https://translate.googleapis.com'])
            ->andReturn('Xin chao');

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil, $pyGoogleTranslateClient);

        $this->assertSame('Xin chao', $util->translateText(9, 3, 'Hello world', 'en', 'vi'));
        $this->assertSame('Xin chao', $util->translateText(9, 3, 'Hello world', 'en', 'vi'));
    }

    public function test_throws_when_default_provider_or_model_is_missing_for_aichat_engine()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $pyGoogleTranslateClient = \Mockery::mock(PyGoogleTranslateClient::class);

        $transcriptUtil->shouldReceive('getTranslationEngine')
            ->once()
            ->with(9)
            ->andReturn('aichat');
        $chatUtil->shouldReceive('buildModelOptions')
            ->once()
            ->with(9, 3)
            ->andReturn([
                'default_provider' => '',
                'default_model' => '',
            ]);
        $pyGoogleTranslateClient->shouldNotReceive('translate');

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil, $pyGoogleTranslateClient);

        $this->expectException(\RuntimeException::class);
        $util->translateText(9, 3, 'Hello', 'en', 'vi');
    }

    public function test_throws_when_active_credential_is_missing_for_aichat_engine()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $pyGoogleTranslateClient = \Mockery::mock(PyGoogleTranslateClient::class);

        $transcriptUtil->shouldReceive('getTranslationEngine')
            ->once()
            ->with(9)
            ->andReturn('aichat');
        $chatUtil->shouldReceive('buildModelOptions')
            ->once()
            ->with(9, 3)
            ->andReturn([
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o-mini',
            ]);
        $chatUtil->shouldReceive('resolveCredentialForChat')
            ->once()
            ->with(3, 9, 'openai')
            ->andReturn(null);
        $transcriptUtil->shouldReceive('getLanguageLabel')
            ->once()
            ->with('en')
            ->andReturn('English');
        $transcriptUtil->shouldReceive('getLanguageLabel')
            ->once()
            ->with('vi')
            ->andReturn('Vietnamese');
        $pyGoogleTranslateClient->shouldNotReceive('translate');

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil, $pyGoogleTranslateClient);

        $this->expectException(\RuntimeException::class);
        $util->translateText(9, 3, 'Hello', 'en', 'vi');
    }

    public function test_translates_text_using_business_default_provider_and_model_when_aichat_engine_is_selected()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);
        $pyGoogleTranslateClient = \Mockery::mock(PyGoogleTranslateClient::class);

        $credential = new ChatCredential();
        $credential->encrypted_api_key = 'encrypted-key';

        $transcriptUtil->shouldReceive('getTranslationEngine')
            ->once()
            ->with(9)
            ->andReturn('aichat');
        $chatUtil->shouldReceive('buildModelOptions')
            ->once()
            ->with(9, 3)
            ->andReturn([
                'default_provider' => 'openai',
                'default_model' => 'gpt-4o-mini',
            ]);
        $chatUtil->shouldReceive('resolveCredentialForChat')
            ->once()
            ->with(3, 9, 'openai')
            ->andReturn($credential);
        $chatUtil->shouldReceive('decryptApiKey')
            ->once()
            ->with('encrypted-key')
            ->andReturn('decrypted-key');
        $transcriptUtil->shouldReceive('getLanguageLabel')
            ->once()
            ->with('en')
            ->andReturn('English');
        $transcriptUtil->shouldReceive('getLanguageLabel')
            ->once()
            ->with('vi')
            ->andReturn('Vietnamese');
        $aiChatUtil->shouldReceive('generateText')
            ->once()
            ->withArgs(function ($provider, $apiKey, $model, $messages) {
                return $provider === 'openai'
                    && $apiKey === 'decrypted-key'
                    && $model === 'gpt-4o-mini'
                    && is_array($messages)
                    && count($messages) === 2;
            })
            ->andReturn('Xin chao');
        $pyGoogleTranslateClient->shouldNotReceive('translate');

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil, $pyGoogleTranslateClient);
        $result = $util->translateText(9, 3, 'Hello', 'en', 'vi');

        $this->assertSame('Xin chao', $result);
    }
}
