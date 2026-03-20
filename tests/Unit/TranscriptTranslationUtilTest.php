<?php

namespace Tests\Unit;

use Modules\Aichat\Entities\ChatCredential;
use Modules\Aichat\Utils\AIChatUtil;
use Modules\Aichat\Utils\ChatUtil;
use Modules\Essentials\Utils\TranscriptTranslationUtil;
use Modules\Essentials\Utils\TranscriptUtil;
use Tests\TestCase;

class TranscriptTranslationUtilTest extends TestCase
{
    public function test_returns_original_text_when_language_pair_is_the_same()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);

        $chatUtil->shouldNotReceive('buildModelOptions');
        $aiChatUtil->shouldNotReceive('generateText');

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil);
        $result = $util->translateText(9, 3, 'Xin chao', 'vi', 'vi');

        $this->assertSame('Xin chao', $result);
    }

    public function test_throws_when_default_provider_or_model_is_missing()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);

        $chatUtil->shouldReceive('buildModelOptions')
            ->once()
            ->with(9, 3)
            ->andReturn([
                'default_provider' => '',
                'default_model' => '',
            ]);

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil);

        $this->expectException(\RuntimeException::class);
        $util->translateText(9, 3, 'Hello', 'en', 'vi');
    }

    public function test_throws_when_active_credential_is_missing()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);

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

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil);

        $this->expectException(\RuntimeException::class);
        $util->translateText(9, 3, 'Hello', 'en', 'vi');
    }

    public function test_translates_text_using_business_default_provider_and_model()
    {
        $chatUtil = \Mockery::mock(ChatUtil::class);
        $aiChatUtil = \Mockery::mock(AIChatUtil::class);
        $transcriptUtil = \Mockery::mock(TranscriptUtil::class);

        $credential = new ChatCredential();
        $credential->encrypted_api_key = 'encrypted-key';

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

        $util = new TranscriptTranslationUtil($chatUtil, $aiChatUtil, $transcriptUtil);
        $result = $util->translateText(9, 3, 'Hello', 'en', 'vi');

        $this->assertSame('Xin chao', $result);
    }
}
