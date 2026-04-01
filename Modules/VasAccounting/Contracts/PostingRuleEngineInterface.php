<?php

namespace Modules\VasAccounting\Contracts;

use Modules\VasAccounting\Application\DTOs\PostingContext;
use Modules\VasAccounting\Application\DTOs\PostingPreview;
use Modules\VasAccounting\Application\DTOs\PostingResult;
use Modules\VasAccounting\Application\DTOs\ReversalContext;
use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;

interface PostingRuleEngineInterface
{
    public function preview(FinanceDocument $document, string $eventType): PostingPreview;

    public function post(FinanceDocument $document, string $eventType, PostingContext $context): PostingResult;

    public function reverse(FinanceDocument $document, string $eventType, ReversalContext $context): PostingResult;
}
