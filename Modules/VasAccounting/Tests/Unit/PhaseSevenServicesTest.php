<?php

namespace Modules\VasAccounting\Tests\Unit;

use Mockery;
use Modules\VasAccounting\Services\BankStatementImportAdapterManager;
use Modules\VasAccounting\Services\EInvoiceAdapterManager;
use Modules\VasAccounting\Services\EnterpriseReportingService;
use Modules\VasAccounting\Services\IntegrationHubService;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalMonitorService;
use Modules\VasAccounting\Services\TaxExportAdapterManager;
use Modules\VasAccounting\Contracts\TreasuryExceptionServiceInterface;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Services\VasPayrollBridgeService;
use Modules\VasAccounting\Services\VasPeriodCloseService;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Utils\EnterpriseFinanceReportUtil;
use Modules\VasAccounting\Utils\EnterprisePlanningReportUtil;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class PhaseSevenServicesTest extends TestCase
{
    public function test_enterprise_reporting_service_supports_phase_seven_reports(): void
    {
        $service = new EnterpriseReportingService(
            Mockery::mock(VasInventoryValuationService::class),
            Mockery::mock(EnterpriseFinanceReportUtil::class),
            Mockery::mock(OperationsAssetReportUtil::class),
            Mockery::mock(EnterprisePlanningReportUtil::class),
            Mockery::mock(VasPeriodCloseService::class),
            Mockery::mock(ExpenseApprovalMonitorService::class)
        );

        $this->assertTrue($service->supports('close_packet'));
        $this->assertTrue($service->supports('operational_health'));
        $this->assertTrue($service->supports('expense_register'));
        $this->assertTrue($service->supports('expense_outstanding'));
        $this->assertTrue($service->supports('expense_escalation_audit'));
        $this->assertTrue($service->supports('purchase_register'));
        $this->assertTrue($service->supports('goods_receipt_register'));
        $this->assertTrue($service->supports('procurement_discrepancies'));
        $this->assertTrue($service->supports('procurement_aging'));
        $this->assertSame('Close Packet', $service->definition('close_packet')['title']);
        $this->assertSame('Expense Register', $service->definition('expense_register')['title']);
        $this->assertSame('Expense Escalation Audit', $service->definition('expense_escalation_audit')['title']);
        $this->assertSame('vasaccounting.reports.expense_escalation_audit', $service->definition('expense_escalation_audit')['route']);
        $this->assertSame('Purchase Register', $service->definition('purchase_register')['title']);
        $this->assertSame('vasaccounting.reports.purchase_register', $service->definition('purchase_register')['route']);
        $this->assertSame('Procurement Discrepancies', $service->definition('procurement_discrepancies')['title']);
        $this->assertSame('vasaccounting.reports.procurement_discrepancies', $service->definition('procurement_discrepancies')['route']);
        $this->assertSame('Procurement Aging', $service->definition('procurement_aging')['title']);
        $this->assertSame('vasaccounting.reports.procurement_aging', $service->definition('procurement_aging')['route']);
    }

    public function test_integration_hub_service_parses_bank_statement_lines(): void
    {
        $service = new IntegrationHubService(
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(BankStatementImportAdapterManager::class),
            Mockery::mock(TaxExportAdapterManager::class),
            Mockery::mock(EInvoiceAdapterManager::class),
            Mockery::mock(EnterpriseFinanceReportUtil::class),
            Mockery::mock(VasPayrollBridgeService::class),
            Mockery::mock(VasPostingService::class),
            Mockery::mock(TreasuryExceptionServiceInterface::class)
        );

        $lines = $service->parseStatementLinesText("2026-03-28|Incoming transfer|1500.50|4500.75\n2026-03-29|Service fee|-20.00|4480.75");

        $this->assertCount(2, $lines);
        $this->assertSame('2026-03-28', $lines[0]['transaction_date']);
        $this->assertSame(1500.5, $lines[0]['amount']);
        $this->assertSame(-20.0, $lines[1]['amount']);
    }
}
