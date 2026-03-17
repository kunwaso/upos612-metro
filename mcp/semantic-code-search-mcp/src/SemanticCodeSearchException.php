<?php

declare(strict_types=1);

namespace SemanticCodeSearchMcp;

use RuntimeException;

final class SemanticCodeSearchException extends RuntimeException
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly ?string $path = null,
        private readonly array $extra = [],
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function toStructuredContent(): array
    {
        $payload = [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
        ];

        if ($this->path !== null) {
            $payload['path'] = $this->path;
        }

        foreach ($this->extra as $key => $value) {
            $payload[$key] = $value;
        }

        return $payload;
    }
}
