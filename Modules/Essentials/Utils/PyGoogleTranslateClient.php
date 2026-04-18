<?php

namespace Modules\Essentials\Utils;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PyGoogleTranslateClient
{
    protected TranscriptUtil $transcriptUtil;

    public function __construct(TranscriptUtil $transcriptUtil)
    {
        $this->transcriptUtil = $transcriptUtil;
    }

    /**
     * @throws \RuntimeException
     */
    public function translate(
        int $businessId,
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        array $serviceUrls = []
    ): string {
        $scriptPath = $this->transcriptUtil->getTranslationPythonScriptPath($businessId);
        if (! is_file($scriptPath)) {
            throw new \RuntimeException(__('essentials::lang.translation_py_script_missing'));
        }

        $pythonBinary = $this->transcriptUtil->getTranslationPythonBinary($businessId);
        $payload = [
            'text' => $text,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'service_urls' => array_values(array_filter(array_map(function ($url) {
                return trim((string) $url);
            }, $serviceUrls))),
        ];

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encodedPayload === false) {
            throw new \RuntimeException(__('essentials::lang.translation_py_failed'));
        }

        $process = new Process([$pythonBinary, $scriptPath]);
        $process->setTimeout($this->transcriptUtil->getTranslationTimeoutSeconds($businessId));
        $process->setInput($encodedPayload);

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw new \RuntimeException(__('essentials::lang.translation_py_timeout'));
        } catch (\Throwable $exception) {
            throw new \RuntimeException(__('essentials::lang.translation_py_runtime_missing'));
        }

        $stdout = trim((string) $process->getOutput());
        $stderr = trim((string) $process->getErrorOutput());

        $decoded = null;
        if ($stdout !== '') {
            $decoded = json_decode($stdout, true);
            if (! is_array($decoded)) {
                throw new \RuntimeException(__('essentials::lang.translation_py_failed'));
            }
        }

        if (is_array($decoded) && ($decoded['ok'] ?? false) === true) {
            $translatedText = trim((string) ($decoded['translated_text'] ?? ''));
            if ($translatedText === '') {
                throw new \RuntimeException(__('essentials::lang.translation_empty_response'));
            }

            return $translatedText;
        }

        if (is_array($decoded)) {
            throw $this->mapScriptErrorToException($decoded);
        }

        if (! $process->isSuccessful()) {
            $stderrLower = strtolower($stderr);
            if (
                str_contains($stderrLower, 'not found')
                || str_contains($stderrLower, 'is not recognized')
                || str_contains($stderrLower, 'no such file or directory')
            ) {
                throw new \RuntimeException(__('essentials::lang.translation_py_runtime_missing'));
            }

            throw new \RuntimeException(__('essentials::lang.translation_py_failed'));
        }

        throw new \RuntimeException(__('essentials::lang.translation_py_failed'));
    }

    protected function mapScriptErrorToException(array $errorPayload): \RuntimeException
    {
        $errorCode = strtoupper(trim((string) ($errorPayload['error_code'] ?? '')));

        if ($errorCode === 'DEPENDENCY_MISSING') {
            return new \RuntimeException(__('essentials::lang.translation_py_dependency_missing'));
        }

        if ($errorCode === 'TIMEOUT') {
            return new \RuntimeException(__('essentials::lang.translation_py_timeout'));
        }

        if ($errorCode === 'UNSUPPORTED_LANGUAGE') {
            return new \RuntimeException(__('essentials::lang.translation_py_language_unsupported'));
        }

        if ($errorCode === 'EMPTY_RESPONSE') {
            return new \RuntimeException(__('essentials::lang.translation_empty_response'));
        }

        return new \RuntimeException(__('essentials::lang.translation_py_failed'));
    }
}
