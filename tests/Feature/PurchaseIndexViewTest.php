<?php

namespace Tests\Feature;

use Tests\TestCase;

class PurchaseIndexViewTest extends TestCase
{
    public function test_purchase_table_partial_renders_custom_column_labels_from_controller_data(): void
    {
        $html = view('purchase.partials.purchase_table', [
            'purchase_custom_labels' => [
                'custom_field_1' => 'Batch no.',
                'custom_field_2' => '',
            ],
        ])->render();

        $this->assertStringContainsString('id="purchase_table"', $html);
        $this->assertStringContainsString('Batch no.', $html);
    }

    public function test_purchase_table_partial_handles_missing_labels_gracefully(): void
    {
        $html = view('purchase.partials.purchase_table', [])->render();

        $this->assertStringContainsString('id="purchase_table"', $html);
    }
}
