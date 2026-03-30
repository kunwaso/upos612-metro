<?php

use Modules\VasAccounting\Http\Controllers\ChartOfAccountsController;
use Modules\VasAccounting\Http\Controllers\BudgetController;
use Modules\VasAccounting\Http\Controllers\CashBankController;
use Modules\VasAccounting\Http\Controllers\ClosingController;
use Modules\VasAccounting\Http\Controllers\ContractController;
use Modules\VasAccounting\Http\Controllers\CostingController;
use Modules\VasAccounting\Http\Controllers\CutoverController;
use Modules\VasAccounting\Http\Controllers\DashboardController;
use Modules\VasAccounting\Http\Controllers\EInvoiceController;
use Modules\VasAccounting\Http\Controllers\FixedAssetController;
use Modules\VasAccounting\Http\Controllers\IntegrationController;
use Modules\VasAccounting\Http\Controllers\InventoryController;
use Modules\VasAccounting\Http\Controllers\InvoiceController;
use Modules\VasAccounting\Http\Controllers\LoanController;
use Modules\VasAccounting\Http\Controllers\PayableController;
use Modules\VasAccounting\Http\Controllers\PayrollController;
use Modules\VasAccounting\Http\Controllers\PeriodController;
use Modules\VasAccounting\Http\Controllers\ReceivableController;
use Modules\VasAccounting\Http\Controllers\ReportController;
use Modules\VasAccounting\Http\Controllers\SetupController;
use Modules\VasAccounting\Http\Controllers\TaxController;
use Modules\VasAccounting\Http\Controllers\ToolsController;
use Modules\VasAccounting\Http\Controllers\VoucherController;
use Modules\VasAccounting\Http\Middleware\ApplyVasLocale;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', ApplyVasLocale::class, 'timezone', 'AdminSidebarMenu'])->group(function () {
    Route::prefix('vas-accounting')->name('vasaccounting.')->group(function () {
        Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
        Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
        Route::post('/setup/bootstrap', [SetupController::class, 'bootstrap'])->name('setup.bootstrap');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

        Route::get('/chart-of-accounts', [ChartOfAccountsController::class, 'index'])->name('chart.index');
        Route::post('/chart-of-accounts', [ChartOfAccountsController::class, 'store'])->name('chart.store');

        Route::get('/periods', [PeriodController::class, 'index'])->name('periods.index');
        Route::post('/periods', [PeriodController::class, 'store'])->name('periods.store');
        Route::post('/periods/{period}/close', [PeriodController::class, 'close'])->whereNumber('period')->name('periods.close');

        Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::get('/vouchers/create', [VoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->whereNumber('voucher')->name('vouchers.show');
        Route::post('/vouchers/{voucher}/post', [VoucherController::class, 'post'])->whereNumber('voucher')->name('vouchers.post');
        Route::post('/vouchers/{voucher}/reverse', [VoucherController::class, 'reverse'])->whereNumber('voucher')->name('vouchers.reverse');

        Route::get('/cash-bank', [CashBankController::class, 'index'])->name('cash_bank.index');
        Route::post('/cash-bank/cashbooks', [CashBankController::class, 'storeCashbook'])->name('cash_bank.cashbooks.store');
        Route::post('/cash-bank/bank-accounts', [CashBankController::class, 'storeBankAccount'])->name('cash_bank.bank_accounts.store');
        Route::post('/cash-bank/statements', [CashBankController::class, 'importStatement'])->name('cash_bank.statements.import');
        Route::post('/cash-bank/statements/lines/{line}/reconcile', [CashBankController::class, 'reconcileLine'])->whereNumber('line')->name('cash_bank.statements.reconcile');
        Route::get('/receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::post('/receivables/allocations', [ReceivableController::class, 'storeAllocation'])->name('receivables.allocations.store');
        Route::get('/payables', [PayableController::class, 'index'])->name('payables.index');
        Route::post('/payables/allocations', [PayableController::class, 'storeAllocation'])->name('payables.allocations.store');
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/warehouses', [InventoryController::class, 'storeWarehouse'])->name('inventory.warehouses.store');
        Route::post('/inventory/documents', [InventoryController::class, 'storeDocument'])->name('inventory.documents.store');
        Route::post('/inventory/documents/{document}/post', [InventoryController::class, 'postDocument'])->whereNumber('document')->name('inventory.documents.post');
        Route::post('/inventory/documents/{document}/reverse', [InventoryController::class, 'reverseDocument'])->whereNumber('document')->name('inventory.documents.reverse');
        Route::get('/tools', [ToolsController::class, 'index'])->name('tools.index');
        Route::post('/tools', [ToolsController::class, 'store'])->name('tools.store');
        Route::post('/tools/amortization/run', [ToolsController::class, 'runAmortization'])->name('tools.amortization.run');
        Route::get('/fixed-assets', [FixedAssetController::class, 'index'])->name('assets.index');
        Route::post('/fixed-assets/categories', [FixedAssetController::class, 'storeCategory'])->name('assets.categories.store');
        Route::post('/fixed-assets', [FixedAssetController::class, 'storeAsset'])->name('assets.store');
        Route::post('/fixed-assets/{asset}/transfer', [FixedAssetController::class, 'transfer'])->whereNumber('asset')->name('assets.transfer');
        Route::post('/fixed-assets/{asset}/dispose', [FixedAssetController::class, 'dispose'])->whereNumber('asset')->name('assets.dispose');
        Route::post('/fixed-assets/depreciation/run', [FixedAssetController::class, 'runDepreciation'])->name('assets.depreciation.run');
        Route::get('/tax', [TaxController::class, 'index'])->name('tax.index');
        Route::post('/tax/export', [TaxController::class, 'export'])->name('tax.export');
        Route::get('/e-invoices', [EInvoiceController::class, 'index'])->name('einvoices.index');
        Route::post('/e-invoices/{voucher}/issue', [EInvoiceController::class, 'issue'])->whereNumber('voucher')->name('einvoices.issue');
        Route::post('/e-invoices/{document}/sync', [EInvoiceController::class, 'sync'])->whereNumber('document')->name('einvoices.sync');
        Route::post('/e-invoices/{document}/cancel', [EInvoiceController::class, 'cancel'])->whereNumber('document')->name('einvoices.cancel');
        Route::post('/e-invoices/{document}/correct', [EInvoiceController::class, 'correct'])->whereNumber('document')->name('einvoices.correct');
        Route::post('/e-invoices/{document}/replace', [EInvoiceController::class, 'replace'])->whereNumber('document')->name('einvoices.replace');
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/bridge', [PayrollController::class, 'bridgeGroup'])->name('payroll.bridge');
        Route::post('/payroll/bridge-payments', [PayrollController::class, 'bridgePayments'])->name('payroll.bridge_payments');
        Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
        Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
        Route::post('/contracts/milestones', [ContractController::class, 'storeMilestone'])->name('contracts.milestones.store');
        Route::post('/contracts/milestones/{milestone}/post', [ContractController::class, 'postMilestone'])->whereNumber('milestone')->name('contracts.milestones.post');
        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
        Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
        Route::post('/loans/{loan}/disburse', [LoanController::class, 'disburse'])->whereNumber('loan')->name('loans.disburse');
        Route::post('/loans/schedules', [LoanController::class, 'storeSchedule'])->name('loans.schedules.store');
        Route::post('/loans/schedules/{schedule}/settle', [LoanController::class, 'settleSchedule'])->whereNumber('schedule')->name('loans.schedules.settle');
        Route::get('/costing', [CostingController::class, 'index'])->name('costing.index');
        Route::post('/costing/departments', [CostingController::class, 'storeDepartment'])->name('costing.departments.store');
        Route::post('/costing/cost-centers', [CostingController::class, 'storeCostCenter'])->name('costing.cost_centers.store');
        Route::post('/costing/projects', [CostingController::class, 'storeProject'])->name('costing.projects.store');
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::post('/budgets/lines', [BudgetController::class, 'storeLine'])->name('budgets.lines.store');
        Route::post('/budgets/{budget}/sync-actuals', [BudgetController::class, 'syncActuals'])->whereNumber('budget')->name('budgets.sync_actuals');
        Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
        Route::post('/integrations/runs', [IntegrationController::class, 'storeRun'])->name('integrations.runs.store');
        Route::post('/integrations/failures/{failure}/retry', [IntegrationController::class, 'retryFailure'])->whereNumber('failure')->name('integrations.failures.retry');

        Route::get('/closing', [ClosingController::class, 'index'])->name('closing.index');
        Route::post('/closing/period/{period}/soft-lock', [ClosingController::class, 'softLock'])->whereNumber('period')->name('closing.soft_lock');
        Route::post('/closing/period/{period}', [ClosingController::class, 'close'])->whereNumber('period')->name('closing.close');
        Route::post('/closing/period/{period}/reopen', [ClosingController::class, 'reopen'])->whereNumber('period')->name('closing.reopen');
        Route::post('/closing/period/{period}/packet', [ClosingController::class, 'packet'])->whereNumber('period')->name('closing.packet');

        Route::prefix('cutover')->name('cutover.')->group(function () {
            Route::get('/', [CutoverController::class, 'index'])->name('index');
            Route::post('/settings', [CutoverController::class, 'updateSettings'])->name('settings.update');
            Route::post('/personas/{persona}', [CutoverController::class, 'updatePersona'])->name('personas.update');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::post('/snapshots', [ReportController::class, 'storeSnapshot'])->name('snapshots.store');
            Route::get('/snapshots/{snapshot}', [ReportController::class, 'showSnapshot'])->whereNumber('snapshot')->name('snapshots.show');
            Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('trial_balance');
            Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->name('general_ledger');
            Route::get('/vat', [ReportController::class, 'vat'])->name('vat');
            Route::get('/cash-book', [ReportController::class, 'cashBook'])->name('cash_book');
            Route::get('/bank-book', [ReportController::class, 'bankBook'])->name('bank_book');
            Route::get('/bank-reconciliation', [ReportController::class, 'bankReconciliation'])->name('bank_reconciliation');
            Route::get('/receivables', [ReportController::class, 'receivables'])->name('receivables');
            Route::get('/payables', [ReportController::class, 'payables'])->name('payables');
            Route::get('/invoice-register', [ReportController::class, 'invoiceRegister'])->name('invoice_register');
            Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('/fixed-assets', [ReportController::class, 'fixedAssets'])->name('fixed_assets');
            Route::get('/payroll-bridge', [ReportController::class, 'payrollBridge'])->name('payroll_bridge');
            Route::get('/contracts', [ReportController::class, 'contracts'])->name('contracts');
            Route::get('/loans', [ReportController::class, 'loans'])->name('loans');
            Route::get('/costing', [ReportController::class, 'costing'])->name('costing');
            Route::get('/budget-variance', [ReportController::class, 'budgetVariance'])->name('budget_variance');
            Route::get('/financial-statements', [ReportController::class, 'financialStatements'])->name('financial_statements');
            Route::get('/close-packet', [ReportController::class, 'closePacket'])->name('close_packet');
            Route::get('/operational-health', [ReportController::class, 'operationalHealth'])->name('operational_health');
        });
    });
});
