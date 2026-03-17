<?php

declare(strict_types=1);

namespace ReadFileCacheMcp;

use RuntimeException;

final class ReadFileException extends RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly ?string $path = null,
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
     * @return array{code: string, message: string, path?: string}
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

        return $payload;
    }
}
