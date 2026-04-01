<?php

namespace Modules\VasAccounting\Application\DTOs;

class ActionContext
{
    public function __construct(
        protected int $userId,
        protected int $businessId,
        protected ?string $reason = null,
        protected ?string $requestId = null,
        protected ?string $ipAddress = null,
        protected ?string $userAgent = null,
        protected array $meta = []
    ) {
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function businessId(): int
    {
        return $this->businessId;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function meta(): array
    {
        return $this->meta;
    }
}
