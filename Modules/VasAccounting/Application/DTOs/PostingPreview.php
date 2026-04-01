<?php

namespace Modules\VasAccounting\Application\DTOs;

class PostingPreview
{
    /**
     * @param PostingPreviewLine[] $lines
     * @param string[] $warnings
     */
    public function __construct(
        public array $lines,
        public string $totalDebit,
        public string $totalCredit,
        public bool $isBalanced,
        public ?int $ruleSetVersion = null,
        public array $warnings = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'lines' => array_map(static fn (PostingPreviewLine $line) => $line->toArray(), $this->lines),
            'total_debit' => $this->totalDebit,
            'total_credit' => $this->totalCredit,
            'is_balanced' => $this->isBalanced,
            'rule_set_version' => $this->ruleSetVersion,
            'warnings' => $this->warnings,
        ];
    }
}
