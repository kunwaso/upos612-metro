<?php

namespace Modules\VasAccounting\Application\DTOs;

class ApprovalStateView
{
    /**
     * @param array<int, array<string, mixed>> $steps
     */
    public function __construct(
        public readonly string $status,
        public readonly ?int $currentStepNo,
        public readonly array $steps
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'current_step_no' => $this->currentStepNo,
            'steps' => $this->steps,
        ];
    }
}
