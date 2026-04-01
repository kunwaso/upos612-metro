<?php

namespace Modules\VasAccounting\Application\DTOs;

class PostingPreviewLine
{
    public function __construct(
        public int $lineNo,
        public ?int $documentLineId,
        public ?int $accountId,
        public string $entrySide,
        public string $amount,
        public ?string $description = null,
        public array $dimensions = [],
        public array $meta = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'line_no' => $this->lineNo,
            'document_line_id' => $this->documentLineId,
            'account_id' => $this->accountId,
            'entry_side' => $this->entrySide,
            'amount' => $this->amount,
            'description' => $this->description,
            'dimensions' => $this->dimensions,
            'meta' => $this->meta,
        ];
    }
}
