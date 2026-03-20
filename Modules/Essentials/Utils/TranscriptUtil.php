<?php

namespace Modules\Essentials\Utils;

use Illuminate\Support\Facades\Http;

class TranscriptUtil
{
    /**
     * Transcribe an audio file using the Groq Whisper API.
     *
     * @param  string  $filePath  Absolute path to the audio file on disk
     * @param  string  $apiKey    Groq API key (Bearer token)
     * @return string             Transcribed text
     *
     * @throws \RuntimeException  On API failure or unexpected response
     */
    public function transcribe(string $filePath, string $apiKey): string
    {
        if (! file_exists($filePath)) {
            throw new \RuntimeException('Audio file not found: ' . $filePath);
        }

        $response = Http::withToken($apiKey)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                'model'           => 'whisper-large-v3-turbo',
                'response_format' => 'json',
            ]);

        if (! $response->successful()) {
            $errorMessage = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException('Groq API error: ' . $errorMessage);
        }

        $text = $response->json('text');

        if (! is_string($text)) {
            throw new \RuntimeException('Groq API returned an unexpected response format.');
        }

        return trim($text);
    }

    /**
     * Retrieve the Groq API key for a given business from essentials settings.
     *
     * @param  int  $business_id
     * @return string|null
     */
    public function getApiKey(int $business_id): ?string
    {
        $settings = session('business.essentials_settings');
        $settings = ! empty($settings) ? json_decode($settings, true) : [];

        $key = $settings['groq_api_key'] ?? null;

        if (empty($key)) {
            $key = env('GROQ_API_KEY') ?: null;
        }

        return $key;
    }
}
