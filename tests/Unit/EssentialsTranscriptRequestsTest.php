<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Modules\Essentials\Http\Requests\PreviewTranscriptRequest;
use Modules\Essentials\Http\Requests\StoreTranscriptRequest;
use Modules\Essentials\Http\Requests\TranslateTranscriptRequest;
use Tests\TestCase;

class EssentialsTranscriptRequestsTest extends TestCase
{
    public function test_preview_request_rejects_invalid_source_language()
    {
        $request = new PreviewTranscriptRequest();
        $validator = Validator::make(
            [
                'source_language' => 'xx',
                'target_language' => 'vi',
                'audio' => UploadedFile::fake()->create('sample.webm', 64, 'audio/webm'),
            ],
            $request->rules(),
            $request->messages()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('source_language', $validator->errors()->toArray());
    }

    public function test_translate_request_requires_text()
    {
        $request = new TranslateTranscriptRequest();
        $validator = Validator::make(
            [
                'source_language' => 'ce',
                'target_language' => 'vi',
            ],
            $request->rules(),
            $request->messages()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('text', $validator->errors()->toArray());
    }

    public function test_store_request_requires_translated_text()
    {
        $request = new StoreTranscriptRequest();
        $validator = Validator::make(
            [
                'source' => 'live',
                'source_language' => 'ce',
                'target_language' => 'vi',
                'transcript_text' => 'Ni hao',
                'recorded_audio' => UploadedFile::fake()->create('recording.webm', 64, 'audio/webm'),
            ],
            $request->rules(),
            $request->messages()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('translated_text', $validator->errors()->toArray());
    }
}
