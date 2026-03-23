<?php

namespace Modules\Aichat\Utils;

use Illuminate\Support\Str;

class ChatSensitiveDataRedactor
{
    public function redactArray(array $payload): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            $redacted[$key] = $this->redactValue($value, is_string($key) ? $key : null);
        }

        return $redacted;
    }

    public function redactText(string $text): string
    {
        $patterns = (array) config('aichat.security.redaction.value_patterns', []);
        $replacement = $this->replacement();

        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || trim($pattern) === '') {
                continue;
            }

            $text = (string) preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    protected function redactValue($value, ?string $key = null)
    {
        if ($this->isSensitiveKey($key)) {
            return $this->replacement();
        }

        if (is_array($value)) {
            return $this->redactArray($value);
        }

        if (is_string($value)) {
            return $this->redactText($value);
        }

        return $value;
    }

    protected function isSensitiveKey(?string $key): bool
    {
        if ($key === null) {
            return false;
        }

        $normalized = Str::lower(trim($key));
        if ($normalized === '') {
            return false;
        }

        $blockedKeys = array_filter((array) config('aichat.security.redaction.blocked_keys', []), function ($item) {
            return is_string($item) && trim($item) !== '';
        });

        foreach ($blockedKeys as $blockedKey) {
            if (Str::contains($normalized, Str::lower((string) $blockedKey))) {
                return true;
            }
        }

        return false;
    }

    protected function replacement(): string
    {
        return (string) config('aichat.security.redaction.replacement', '[redacted]');
    }
}

