<?php

namespace Modules\VasAccounting\Application\DTOs;

class DocumentUpdateData
{
    public function __construct(
        public array $attributes,
        public ?array $lines = null
    ) {
    }
}
