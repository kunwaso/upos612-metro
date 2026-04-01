<?php

namespace Modules\VasAccounting\Tests\Unit;

use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class FinanceCoreConfigTest extends TestCase
{
    public function test_finance_core_feature_flags_and_defaults_are_registered(): void
    {
        $this->assertTrue(config('vasaccounting.feature_flags.finance_core_v2'));
        $this->assertTrue(config('vasaccounting.feature_flags.posting_engine_v2'));
        $this->assertTrue(config('vasaccounting.feature_flags.traceability_v2'));
        $this->assertTrue(config('vasaccounting.feature_flags.expense_v2'));
        $this->assertFalse(config('vasaccounting.feature_flags.p2p_v2'));
        $this->assertSame('post', config('vasaccounting.finance_core_defaults.default_event_type'));
        $this->assertContains('document_line_credit', config('vasaccounting.finance_core_defaults.supported_account_sources'));
        $this->assertContains('matched', config('vasaccounting.finance_core_defaults.supported_workflow_statuses'));
        $this->assertContains('partially_collected', config('vasaccounting.finance_core_defaults.supported_workflow_statuses'));
        $this->assertContains('collected', config('vasaccounting.finance_core_defaults.supported_workflow_statuses'));
        $this->assertSame('manual', data_get(config('vasaccounting.finance_document_blueprints'), 'payables.supplier_invoice.posting_mode'));
        $this->assertSame('manual', data_get(config('vasaccounting.finance_document_blueprints'), 'cash_bank.cash_transfer.posting_mode'));
        $this->assertSame('manual', data_get(config('vasaccounting.finance_document_blueprints'), 'cash_bank.bank_transfer.posting_mode'));
        $this->assertSame('manual', data_get(config('vasaccounting.finance_document_blueprints'), 'cash_bank.petty_cash_expense.posting_mode'));
        $this->assertSame('manual', data_get(config('vasaccounting.finance_document_blueprints'), 'expense_management.expense_claim.posting_mode'));
        $this->assertContains('advance_settlement', data_get(config('vasaccounting.finance_document_blueprints'), 'expense_management.advance_request.allowed_child_types'));
        $this->assertSame('EXPENSE_CLAIM_STANDARD', data_get(config('vasaccounting.approval_defaults.expense_document_policies'), 'expense_claim.tiers.0.policy_code'));
        $this->assertSame('finance_manager', data_get(config('vasaccounting.approval_defaults.expense_document_policies'), 'advance_request.tiers.1.steps.1.approver_role'));
        $this->assertContains('purchase_order', data_get(config('vasaccounting.finance_document_blueprints'), 'procurement.purchase_requisition.allowed_child_types'));
        $this->assertTrue(config('vasaccounting.approval_defaults.finance_document_defaults.maker_checker'));
        $this->assertTrue(config('vasaccounting.finance_matching.supplier_invoice.require_parent_link'));
        $this->assertTrue(config('vasaccounting.finance_matching.supplier_invoice.allow_purchase_order_only'));
        $this->assertSame('0.0100', config('vasaccounting.finance_matching.supplier_invoice.amount_variance_tolerance'));
        $this->assertSame('weighted_average', config('vasaccounting.inventory_ledger_defaults.costing_method'));
        $this->assertFalse(config('vasaccounting.inventory_ledger_defaults.allow_negative_stock'));
    }

    public function test_finance_core_domain_is_available_in_enterprise_domain_registry(): void
    {
        $domains = (new VasAccountingUtil())->enterpriseDomains();

        $this->assertArrayHasKey('finance_core', $domains);
        $this->assertArrayHasKey('expenses', $domains);
        $this->assertSame('vasaccounting.dashboard.index', data_get($domains, 'finance_core.route'));
        $this->assertSame('vasaccounting.expenses.index', data_get($domains, 'expenses.route'));
        $this->assertSame('vas_fin_documents', data_get($domains, 'finance_core.record_table'));
    }
}
