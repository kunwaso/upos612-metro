<?php

namespace Modules\VasAccounting\Application\DTOs;

class DocumentTraceView
{
    public function __construct(
        public array $document,
        public array $events,
        public array $journals,
        public array $links,
        public array $openItems = [],
        public array $matches = [],
        public array $inventoryMovements = [],
        public array $treasuryReconciliations = [],
        public ?array $o2cSummary = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'document' => $this->document,
            'events' => $this->events,
            'journals' => $this->journals,
            'links' => $this->links,
            'open_items' => $this->openItems,
            'matches' => $this->matches,
            'inventory_movements' => $this->inventoryMovements,
            'treasury_reconciliations' => $this->treasuryReconciliations,
            'o2c_summary' => $this->o2cSummary,
        ];
    }
}
