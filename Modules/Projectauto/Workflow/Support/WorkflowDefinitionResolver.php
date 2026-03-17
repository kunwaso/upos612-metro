<?php

namespace Modules\Projectauto\Workflow\Support;

class WorkflowDefinitionResolver
{
    protected PredefinedRuleCatalog $catalog;

    public function __construct(PredefinedRuleCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    public function resolve(): array
    {
        $catalog = $this->catalog->catalog();

        return [
            'families' => $catalog['families'],
            'triggers' => array_values($catalog['triggers']),
            'logic' => array_values($catalog['logic']),
            'actions' => array_values($catalog['actions']),
            'operators' => $catalog['operators'],
            'conditions' => $catalog['conditions'],
        ];
    }
}
