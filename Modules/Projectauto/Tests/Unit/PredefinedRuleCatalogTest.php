<?php

namespace Modules\Projectauto\Tests\Unit;

use Modules\Projectauto\Entities\ProjectautoRule;
use Modules\Projectauto\Http\Controllers\DataController;
use Modules\Projectauto\Workflow\NodeRegistry;
use Modules\Projectauto\Workflow\Support\PredefinedRuleCatalog;
use Tests\TestCase;

class PredefinedRuleCatalogTest extends TestCase
{
    public function test_registered_triggers_match_rule_constants_and_data_hooks()
    {
        $catalog = new PredefinedRuleCatalog(new NodeRegistry());
        $triggerTypes = array_keys($catalog->catalog()['triggers']);
        $ruleTriggers = [
            ProjectautoRule::TRIGGER_PAYMENT_STATUS_UPDATED,
            ProjectautoRule::TRIGGER_SALES_ORDER_STATUS_UPDATED,
        ];

        sort($triggerTypes);
        sort($ruleTriggers);

        $this->assertSame($ruleTriggers, $triggerTypes);

        foreach ($triggerTypes as $triggerType) {
            $this->assertTrue(method_exists(DataController::class, 'after_' . $triggerType));
        }
    }

    public function test_bounded_action_fields_expose_finite_choice_metadata()
    {
        $catalog = new PredefinedRuleCatalog(new NodeRegistry());
        $actions = $catalog->catalog()['actions'];

        $addProductSchema = collect($actions['add_product']['config_schema'] ?? []);
        $barcodeTypeField = $addProductSchema->firstWhere('key', 'barcode_type');
        $this->assertSame('select', $barcodeTypeField['type'] ?? null);
        $this->assertNotEmpty($barcodeTypeField['options'] ?? []);

        $createInvoiceSchema = collect($actions['create_invoice']['config_schema'] ?? []);
        $paymentsField = $createInvoiceSchema->firstWhere('key', 'payments');
        $paymentMethodField = collect($paymentsField['children'] ?? [])->firstWhere('key', 'method');
        $this->assertSame('select', $paymentMethodField['type'] ?? null);
        $this->assertNotEmpty($paymentMethodField['options'] ?? []);
    }
}
