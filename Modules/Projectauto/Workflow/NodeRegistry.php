<?php

namespace Modules\Projectauto\Workflow;

use Modules\Projectauto\Workflow\Nodes\Actions\AddProductActionNode;
use Modules\Projectauto\Workflow\Nodes\Actions\AdjustStockActionNode;
use Modules\Projectauto\Workflow\Nodes\Actions\CreateInvoiceActionNode;
use Modules\Projectauto\Workflow\Nodes\Logic\IfElseNode;
use Modules\Projectauto\Workflow\Nodes\Triggers\PaymentStatusUpdatedNode;
use Modules\Projectauto\Workflow\Nodes\Triggers\SalesOrderStatusUpdatedNode;

class NodeRegistry
{
    public function definitions(): array
    {
        $nodes = [
            new PaymentStatusUpdatedNode(),
            new SalesOrderStatusUpdatedNode(),
            new IfElseNode(),
            new CreateInvoiceActionNode(),
            new AddProductActionNode(),
            new AdjustStockActionNode(),
        ];

        $definitions = [];
        foreach ($nodes as $node) {
            $definition = $node->definition();
            $definition['config_schema'] = $this->normalizeSchema($definition['config_schema'] ?? []);
            $definitions[$definition['type']] = $definition;
        }

        return $definitions;
    }

    protected function normalizeSchema(array $schema): array
    {
        $normalized = [];

        foreach ($schema as $field) {
            $item = [
                'key' => $field['key'],
                'label' => $field['label'] ?? ucfirst(str_replace('_', ' ', $field['key'])),
                'type' => $field['type'] ?? 'text',
                'required' => (bool) ($field['required'] ?? false),
            ];

            if (array_key_exists('options', $field)) {
                $item['options'] = array_values($field['options']);
            }

            if (array_key_exists('enum', $field)) {
                $item['enum'] = array_values($field['enum']);
            }

            if (isset($field['children']) && is_array($field['children'])) {
                $item['children'] = $this->normalizeSchema($field['children']);
            }

            foreach (['min_items', 'placeholder', 'description'] as $key) {
                if (array_key_exists($key, $field)) {
                    $item[$key] = $field[$key];
                }
            }

            $normalized[] = $item;
        }

        return $normalized;
    }
}
