<?php

namespace Modules\VasAccounting\Tests\Feature;

use Modules\VasAccounting\Http\Middleware\RedirectLegacyAccountingToVas;
use Tests\TestCase;

class VasAccountingRoutesRegistrationTest extends TestCase
{
    public function test_vas_accounting_named_routes_are_registered(): void
    {
        $router = app('router');

        $this->assertTrue($router->has('vasaccounting.setup.index'));
        $this->assertTrue($router->has('vasaccounting.dashboard.index'));
        $this->assertTrue($router->has('vasaccounting.chart.index'));
        $this->assertTrue($router->has('vasaccounting.periods.index'));
        $this->assertTrue($router->has('vasaccounting.vouchers.index'));
        $this->assertTrue($router->has('vasaccounting.cash_bank.index'));
        $this->assertTrue($router->has('vasaccounting.cash_bank.cashbooks.store'));
        $this->assertTrue($router->has('vasaccounting.cash_bank.bank_accounts.store'));
        $this->assertTrue($router->has('vasaccounting.cash_bank.statements.import'));
        $this->assertTrue($router->has('vasaccounting.cash_bank.statements.reconcile'));
        $this->assertTrue($router->has('vasaccounting.receivables.index'));
        $this->assertTrue($router->has('vasaccounting.receivables.allocations.store'));
        $this->assertTrue($router->has('vasaccounting.payables.index'));
        $this->assertTrue($router->has('vasaccounting.payables.allocations.store'));
        $this->assertTrue($router->has('vasaccounting.invoices.index'));
        $this->assertTrue($router->has('vasaccounting.inventory.index'));
        $this->assertTrue($router->has('vasaccounting.inventory.warehouses.store'));
        $this->assertTrue($router->has('vasaccounting.inventory.documents.store'));
        $this->assertTrue($router->has('vasaccounting.inventory.documents.post'));
        $this->assertTrue($router->has('vasaccounting.inventory.documents.reverse'));
        $this->assertTrue($router->has('vasaccounting.tools.index'));
        $this->assertTrue($router->has('vasaccounting.tools.store'));
        $this->assertTrue($router->has('vasaccounting.tools.amortization.run'));
        $this->assertTrue($router->has('vasaccounting.assets.index'));
        $this->assertTrue($router->has('vasaccounting.assets.categories.store'));
        $this->assertTrue($router->has('vasaccounting.assets.store'));
        $this->assertTrue($router->has('vasaccounting.assets.transfer'));
        $this->assertTrue($router->has('vasaccounting.assets.dispose'));
        $this->assertTrue($router->has('vasaccounting.tax.index'));
        $this->assertTrue($router->has('vasaccounting.tax.export'));
        $this->assertTrue($router->has('vasaccounting.einvoices.index'));
        $this->assertTrue($router->has('vasaccounting.einvoices.cancel'));
        $this->assertTrue($router->has('vasaccounting.einvoices.correct'));
        $this->assertTrue($router->has('vasaccounting.einvoices.replace'));
        $this->assertTrue($router->has('vasaccounting.payroll.index'));
        $this->assertTrue($router->has('vasaccounting.payroll.bridge'));
        $this->assertTrue($router->has('vasaccounting.payroll.bridge_payments'));
        $this->assertTrue($router->has('vasaccounting.contracts.index'));
        $this->assertTrue($router->has('vasaccounting.contracts.store'));
        $this->assertTrue($router->has('vasaccounting.contracts.milestones.store'));
        $this->assertTrue($router->has('vasaccounting.contracts.milestones.post'));
        $this->assertTrue($router->has('vasaccounting.loans.index'));
        $this->assertTrue($router->has('vasaccounting.loans.store'));
        $this->assertTrue($router->has('vasaccounting.loans.disburse'));
        $this->assertTrue($router->has('vasaccounting.loans.schedules.store'));
        $this->assertTrue($router->has('vasaccounting.loans.schedules.settle'));
        $this->assertTrue($router->has('vasaccounting.costing.index'));
        $this->assertTrue($router->has('vasaccounting.costing.departments.store'));
        $this->assertTrue($router->has('vasaccounting.costing.cost_centers.store'));
        $this->assertTrue($router->has('vasaccounting.costing.projects.store'));
        $this->assertTrue($router->has('vasaccounting.budgets.index'));
        $this->assertTrue($router->has('vasaccounting.budgets.store'));
        $this->assertTrue($router->has('vasaccounting.budgets.lines.store'));
        $this->assertTrue($router->has('vasaccounting.budgets.sync_actuals'));
        $this->assertTrue($router->has('vasaccounting.integrations.index'));
        $this->assertTrue($router->has('vasaccounting.integrations.runs.store'));
        $this->assertTrue($router->has('vasaccounting.integrations.failures.retry'));
        $this->assertTrue($router->has('vasaccounting.closing.index'));
        $this->assertTrue($router->has('vasaccounting.closing.soft_lock'));
        $this->assertTrue($router->has('vasaccounting.closing.reopen'));
        $this->assertTrue($router->has('vasaccounting.closing.packet'));
        $this->assertTrue($router->has('vasaccounting.cutover.index'));
        $this->assertTrue($router->has('vasaccounting.cutover.settings.update'));
        $this->assertTrue($router->has('vasaccounting.cutover.personas.update'));
        $this->assertTrue($router->has('vasaccounting.reports.index'));
        $this->assertTrue($router->has('vasaccounting.reports.snapshots.store'));
        $this->assertTrue($router->has('vasaccounting.reports.snapshots.show'));
        $this->assertTrue($router->has('vasaccounting.reports.cash_book'));
        $this->assertTrue($router->has('vasaccounting.reports.bank_book'));
        $this->assertTrue($router->has('vasaccounting.reports.bank_reconciliation'));
        $this->assertTrue($router->has('vasaccounting.reports.receivables'));
        $this->assertTrue($router->has('vasaccounting.reports.payables'));
        $this->assertTrue($router->has('vasaccounting.reports.invoice_register'));
        $this->assertTrue($router->has('vasaccounting.reports.payroll_bridge'));
        $this->assertTrue($router->has('vasaccounting.reports.contracts'));
        $this->assertTrue($router->has('vasaccounting.reports.loans'));
        $this->assertTrue($router->has('vasaccounting.reports.costing'));
        $this->assertTrue($router->has('vasaccounting.reports.budget_variance'));
        $this->assertTrue($router->has('vasaccounting.reports.close_packet'));
        $this->assertTrue($router->has('vasaccounting.reports.operational_health'));
        $this->assertTrue($router->has('vasaccounting.api.health'));
        $this->assertTrue($router->has('vasaccounting.api.domains'));
        $this->assertTrue($router->has('vasaccounting.api.snapshots'));
        $this->assertTrue($router->has('vasaccounting.api.integration_runs'));
        $this->assertTrue($router->has('vasaccounting.api.webhooks.store'));
    }

    public function test_setup_and_report_routes_use_expected_uris(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertSame(
            'vas-accounting/setup',
            $routes->getByName('vasaccounting.setup.index')->uri()
        );
        $this->assertSame(
            'vas-accounting/reports/financial-statements',
            $routes->getByName('vasaccounting.reports.financial_statements')->uri()
        );
        $this->assertSame(
            'vas-accounting/cash-bank',
            $routes->getByName('vasaccounting.cash_bank.index')->uri()
        );
        $this->assertSame(
            'vas-accounting/reports/bank-reconciliation',
            $routes->getByName('vasaccounting.reports.bank_reconciliation')->uri()
        );
        $this->assertSame(
            'api/vas-accounting/domains',
            $routes->getByName('vasaccounting.api.domains')->uri()
        );
        $this->assertSame(
            'vas-accounting/payroll/bridge',
            $routes->getByName('vasaccounting.payroll.bridge')->uri()
        );
        $this->assertSame(
            'vas-accounting/reports/budget-variance',
            $routes->getByName('vasaccounting.reports.budget_variance')->uri()
        );
        $this->assertSame(
            'vas-accounting/integrations/runs',
            $routes->getByName('vasaccounting.integrations.runs.store')->uri()
        );
        $this->assertSame(
            'vas-accounting/closing/period/{period}/soft-lock',
            $routes->getByName('vasaccounting.closing.soft_lock')->uri()
        );
        $this->assertSame(
            'vas-accounting/cutover',
            $routes->getByName('vasaccounting.cutover.index')->uri()
        );
        $this->assertSame(
            'vas-accounting/cutover/settings',
            $routes->getByName('vasaccounting.cutover.settings.update')->uri()
        );
        $this->assertSame(
            'vas-accounting/inventory/documents/{document}/post',
            $routes->getByName('vasaccounting.inventory.documents.post')->uri()
        );
        $this->assertSame(
            'vas-accounting/reports/operational-health',
            $routes->getByName('vasaccounting.reports.operational_health')->uri()
        );
        $this->assertSame(
            'api/vas-accounting/webhooks/{provider}',
            $routes->getByName('vasaccounting.api.webhooks.store')->uri()
        );
    }

    public function test_legacy_account_routes_are_guarded_by_cutover_middleware(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertContains(
            RedirectLegacyAccountingToVas::class,
            $routes->getByName('account.index')->gatherMiddleware()
        );
        $this->assertContains(
            RedirectLegacyAccountingToVas::class,
            $routes->getByName('payment-account.index')->gatherMiddleware()
        );
        $this->assertContains(
            RedirectLegacyAccountingToVas::class,
            $routes->getByName('account-types.index')->gatherMiddleware()
        );
    }
}
