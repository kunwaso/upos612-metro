<?php

namespace Modules\VasAccounting\Providers;

use App\Events\ExpenseCreatedOrModified;
use App\Events\PurchaseCreatedOrModified;
use App\Events\SellCreatedOrModified;
use App\Events\StockAdjustmentCreatedOrModified;
use App\Events\StockTransferCreatedOrModified;
use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Transaction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Modules\VasAccounting\Console\ClosePeriodCommand;
use Modules\VasAccounting\Console\CutoverBackfillCommand;
use Modules\VasAccounting\Console\CutoverParityCommand;
use Modules\VasAccounting\Console\ProvidersHealthCommand;
use Modules\VasAccounting\Console\ReplayPostingsCommand;
use Modules\VasAccounting\Console\RunDepreciationCommand;
use Modules\VasAccounting\Console\SeedVnChartCommand;
use Modules\VasAccounting\Contracts\AccountDerivationServiceInterface;
use Modules\VasAccounting\Contracts\ApprovalWorkflowServiceInterface;
use Modules\VasAccounting\Contracts\DocumentMatchingServiceInterface;
use Modules\VasAccounting\Contracts\DocumentTraceServiceInterface;
use Modules\VasAccounting\Contracts\EInvoiceAdapterInterface;
use Modules\VasAccounting\Contracts\ExpenseSettlementServiceInterface;
use Modules\VasAccounting\Contracts\FinanceDocumentServiceInterface;
use Modules\VasAccounting\Contracts\InventoryCostServiceInterface;
use Modules\VasAccounting\Contracts\OpenItemServiceInterface;
use Modules\VasAccounting\Contracts\OrderToCashLifecycleServiceInterface;
use Modules\VasAccounting\Contracts\PostingRuleEngineInterface;
use Modules\VasAccounting\Contracts\ProcurementDiscrepancyServiceInterface;
use Modules\VasAccounting\Contracts\TreasuryReconciliationServiceInterface;
use Modules\VasAccounting\Contracts\TreasuryExceptionServiceInterface;
use Modules\VasAccounting\Services\FinanceCore\AccountDerivationService;
use Modules\VasAccounting\Services\FinanceCore\DocumentMatchingService;
use Modules\VasAccounting\Services\FinanceCore\DocumentTraceService;
use Modules\VasAccounting\Services\FinanceCore\DocumentWorkflowService;
use Modules\VasAccounting\Services\FinanceCore\FinanceDocumentCommandService;
use Modules\VasAccounting\Services\FinanceCore\PostingRuleEngineService;
use Modules\VasAccounting\Services\Subledger\OpenItemService;
use Modules\VasAccounting\Services\Treasury\TreasuryReconciliationService;
use Modules\VasAccounting\Services\Treasury\TreasuryExceptionService;
use Modules\VasAccounting\Services\WorkflowApproval\ApprovalWorkflowService;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalEscalationDispatchService;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalMonitorService;
use Modules\VasAccounting\Services\WorkflowApproval\ExpenseApprovalPolicyResolver;
use Modules\VasAccounting\Services\WorkflowApproval\MakerCheckerGuard;
use Modules\VasAccounting\Services\BudgetControlService;
use Modules\VasAccounting\Services\ContractAccountingService;
use Modules\VasAccounting\Services\CutoverParityService;
use Modules\VasAccounting\Services\CutoverService;
use Modules\VasAccounting\Services\LegacyAccountingBackfillService;
use Modules\VasAccounting\Services\Adapters\SandboxEInvoiceAdapter;
use Modules\VasAccounting\Services\BankStatementImportAdapterManager;
use Modules\VasAccounting\Services\EnterpriseReportingService;
use Modules\VasAccounting\Services\EInvoiceAdapterManager;
use Modules\VasAccounting\Services\Expense\ExpenseSettlementService;
use Modules\VasAccounting\Services\IntegrationHubService;
use Modules\VasAccounting\Services\Inventory\InventoryCostService;
use Modules\VasAccounting\Services\LoanAccountingService;
use Modules\VasAccounting\Services\PayrollBridgeManager;
use Modules\VasAccounting\Services\ProviderHealthService;
use Modules\VasAccounting\Services\Procurement\ProcurementDiscrepancyService;
use Modules\VasAccounting\Services\ReportSnapshotService;
use Modules\VasAccounting\Services\Sales\OrderToCashLifecycleService;
use Modules\VasAccounting\Services\SourceDocumentAdapterManager;
use Modules\VasAccounting\Services\TaxExportAdapterManager;
use Modules\VasAccounting\Services\VasDepreciationService;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Services\VasPeriodCloseService;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Services\VasPayrollBridgeService;
use Modules\VasAccounting\Services\VasToolAmortizationService;
use Modules\VasAccounting\Services\VasWarehouseDocumentService;
use Modules\VasAccounting\Utils\EnterprisePlanningReportUtil;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class VasAccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerAssets();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerEventListeners();
        $this->registerTransactionHooks();
        $this->registerViewComposer();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerServices();
        $this->registerCommands();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('vasaccounting.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'vasaccounting');
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/vasaccounting');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', 'vasaccounting-module-views']);

        $paths = array_merge(
            array_map(function ($path) {
                return $path . '/modules/vasaccounting';
            }, config('view.paths')),
            [$sourcePath]
        );

        $this->loadViewsFrom(array_values(array_filter($paths, 'is_dir')), 'vasaccounting');
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/vasaccounting');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'vasaccounting');

            return;
        }

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'vasaccounting');
    }

    protected function registerAssets(): void
    {
        $this->publishes([
            __DIR__ . '/../Resources/assets' => public_path('modules/vasaccounting'),
        ], 'vasaccounting-assets');
    }

    protected function registerServices(): void
    {
        $this->app->singleton(VasAccountingUtil::class);
        $this->app->singleton(LedgerPostingUtil::class);
        $this->app->singleton(SourceDocumentAdapterManager::class);
        $this->app->singleton(VasPostingService::class);
        $this->app->singleton(VasPeriodCloseService::class);
        $this->app->singleton(VasInventoryValuationService::class);
        $this->app->singleton(VasDepreciationService::class);
        $this->app->singleton(VasToolAmortizationService::class);
        $this->app->singleton(EInvoiceAdapterManager::class);
        $this->app->singleton(BankStatementImportAdapterManager::class);
        $this->app->singleton(TaxExportAdapterManager::class);
        $this->app->singleton(PayrollBridgeManager::class);
        $this->app->singleton(VasPayrollBridgeService::class);
        $this->app->singleton(ContractAccountingService::class);
        $this->app->singleton(LoanAccountingService::class);
        $this->app->singleton(BudgetControlService::class);
        $this->app->singleton(CutoverParityService::class);
        $this->app->singleton(CutoverService::class);
        $this->app->singleton(LegacyAccountingBackfillService::class);
        $this->app->singleton(OperationsAssetReportUtil::class);
        $this->app->singleton(ProviderHealthService::class);
        $this->app->singleton(EnterprisePlanningReportUtil::class);
        $this->app->singleton(EnterpriseReportingService::class);
        $this->app->singleton(ReportSnapshotService::class);
        $this->app->singleton(IntegrationHubService::class);
        $this->app->singleton(VasWarehouseDocumentService::class);
        $this->app->singleton(AccountDerivationService::class);
        $this->app->singleton(DocumentMatchingService::class);
        $this->app->singleton(DocumentTraceService::class);
        $this->app->singleton(DocumentWorkflowService::class);
        $this->app->singleton(ExpenseSettlementService::class);
        $this->app->singleton(ExpenseApprovalEscalationDispatchService::class);
        $this->app->singleton(ExpenseApprovalMonitorService::class);
        $this->app->singleton(ExpenseApprovalPolicyResolver::class);
        $this->app->singleton(MakerCheckerGuard::class);
        $this->app->singleton(ApprovalWorkflowService::class);
        $this->app->singleton(FinanceDocumentCommandService::class);
        $this->app->singleton(InventoryCostService::class);
        $this->app->singleton(OpenItemService::class);
        $this->app->singleton(OrderToCashLifecycleService::class);
        $this->app->singleton(PostingRuleEngineService::class);
        $this->app->singleton(ProcurementDiscrepancyService::class);
        $this->app->singleton(TreasuryReconciliationService::class);
        $this->app->singleton(TreasuryExceptionService::class);
        $this->app->bind(AccountDerivationServiceInterface::class, AccountDerivationService::class);
        $this->app->bind(ApprovalWorkflowServiceInterface::class, ApprovalWorkflowService::class);
        $this->app->bind(DocumentMatchingServiceInterface::class, DocumentMatchingService::class);
        $this->app->bind(DocumentTraceServiceInterface::class, DocumentTraceService::class);
        $this->app->bind(EInvoiceAdapterInterface::class, SandboxEInvoiceAdapter::class);
        $this->app->bind(ExpenseSettlementServiceInterface::class, ExpenseSettlementService::class);
        $this->app->bind(FinanceDocumentServiceInterface::class, FinanceDocumentCommandService::class);
        $this->app->bind(InventoryCostServiceInterface::class, InventoryCostService::class);
        $this->app->bind(OpenItemServiceInterface::class, OpenItemService::class);
        $this->app->bind(OrderToCashLifecycleServiceInterface::class, OrderToCashLifecycleService::class);
        $this->app->bind(PostingRuleEngineInterface::class, PostingRuleEngineService::class);
        $this->app->bind(ProcurementDiscrepancyServiceInterface::class, ProcurementDiscrepancyService::class);
        $this->app->bind(TreasuryReconciliationServiceInterface::class, TreasuryReconciliationService::class);
        $this->app->bind(TreasuryExceptionServiceInterface::class, TreasuryExceptionService::class);
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            SeedVnChartCommand::class,
            ReplayPostingsCommand::class,
            RunDepreciationCommand::class,
            ClosePeriodCommand::class,
            CutoverBackfillCommand::class,
            CutoverParityCommand::class,
            ProvidersHealthCommand::class,
        ]);
    }

    protected function registerEventListeners(): void
    {
        Event::listen(SellCreatedOrModified::class, function (SellCreatedOrModified $event) {
            app(VasPostingService::class)->queueSourceDocument('sell', $event->transaction);
        });

        Event::listen(PurchaseCreatedOrModified::class, function (PurchaseCreatedOrModified $event) {
            $context = [
                'is_deleted' => (bool) $event->isDeleted,
            ];

            if ($event->isDeleted) {
                $context['source_snapshot'] = $this->transactionSnapshot($event->transaction);
            }

            app(VasPostingService::class)->queueSourceDocument('purchase', $event->transaction, $context);
        });

        Event::listen(ExpenseCreatedOrModified::class, function (ExpenseCreatedOrModified $event) {
            app(VasPostingService::class)->queueSourceDocument('expense', $event->expense, [
                'is_deleted' => (bool) $event->isDeleted,
            ]);
        });

        Event::listen(StockAdjustmentCreatedOrModified::class, function (StockAdjustmentCreatedOrModified $event) {
            $stockAdjustment = $event->stockAdjustment;
            $action = (string) $event->action;
            $context = [
                'action' => $action,
            ];

            // On delete flows the transaction may already be removed before posting runs
            // (especially with after-commit + sync queue). Capture minimal source data so
            // adapters can build a safe payload without hard failing on findOrFail().
            if ($action === 'deleted') {
                $context['is_deleted'] = true;
                $context['source_snapshot'] = [
                    'id' => (int) ($stockAdjustment->id ?? 0),
                    'business_id' => (int) ($stockAdjustment->business_id ?? 0),
                    'location_id' => (int) ($stockAdjustment->location_id ?? 0),
                    'transaction_date' => $stockAdjustment->transaction_date ?? null,
                    'ref_no' => $stockAdjustment->ref_no ?? null,
                    'created_by' => (int) ($stockAdjustment->created_by ?? 0),
                    'final_total' => (float) ($stockAdjustment->final_total ?? 0),
                ];
            }

            try {
                app(VasPostingService::class)->queueSourceDocument('stock_adjustment', $stockAdjustment, $context);
            } catch (\Throwable $e) {
                \Log::warning('VAS stock adjustment posting queue failed', [
                    'transaction_id' => (int) ($stockAdjustment->id ?? 0),
                    'business_id' => (int) ($stockAdjustment->business_id ?? 0),
                    'action' => $action,
                    'message' => $e->getMessage(),
                ]);
            }
        });

        Event::listen(StockTransferCreatedOrModified::class, function (StockTransferCreatedOrModified $event) {
            app(VasPostingService::class)->queueSourceDocument('stock_transfer', $event->stock, [
                'action' => (string) $event->action,
            ]);
        });

        Event::listen(TransactionPaymentAdded::class, function (TransactionPaymentAdded $event) {
            app(VasPostingService::class)->queueSourceDocument('transaction_payment', $event->transactionPayment, [
                'form_input' => $event->formInput,
            ]);
        });

        Event::listen(TransactionPaymentUpdated::class, function (TransactionPaymentUpdated $event) {
            app(VasPostingService::class)->queueSourceDocument('transaction_payment', $event->transactionPayment, [
                'transaction_type' => $event->transactionType,
            ]);
        });

        Event::listen(TransactionPaymentDeleted::class, function (TransactionPaymentDeleted $event) {
            $payment = $event->transactionPayment;
            $context = [
                'is_deleted' => (bool) $event->isDeleted,
            ];

            if ($event->isDeleted) {
                $context['source_snapshot'] = $this->paymentSnapshot($payment);

                $transaction = null;
                if (method_exists($payment, 'relationLoaded') && $payment->relationLoaded('transaction')) {
                    $transaction = $payment->transaction;
                } elseif (! empty($payment->transaction_id)) {
                    $transaction = Transaction::find((int) $payment->transaction_id);
                }

                if ($transaction instanceof Transaction) {
                    $context['transaction_snapshot'] = $this->transactionSnapshot($transaction);
                }
            }

            app(VasPostingService::class)->queueSourceDocument('transaction_payment', $payment, $context);
        });
    }

    protected function registerTransactionHooks(): void
    {
        Transaction::saved(function (Transaction $transaction) {
            if (! in_array($transaction->type, ['sell_return', 'purchase_return', 'opening_stock'], true)) {
                return;
            }

            app(VasPostingService::class)->queueSourceDocument((string) $transaction->type, $transaction);
        });
    }

    protected function registerViewComposer(): void
    {
        View::composer('vasaccounting::*', function ($view) {
            $vasUtil = $this->app->make(VasAccountingUtil::class);
            $routeName = (string) optional(request()->route())->getName();
            $locale = app()->getLocale();

            if (Str::startsWith($routeName, 'vasaccounting.')) {
                $businessId = (int) request()->session()->get('user.business_id');
                $locale = $vasUtil->applyVasLocale($businessId > 0 ? $businessId : null, request());
            }

            $view->with('vasAccountingUtil', $vasUtil);
            $view->with('vasAccountingLocale', $locale);
        });

        View::composer('layouts.app', function ($view) {
            $routeName = (string) optional(request()->route())->getName();

            if (Str::startsWith($routeName, 'vasaccounting.')) {
                $businessId = (int) request()->session()->get('user.business_id');
                if ($businessId > 0) {
                    $locale = $this->app->make(VasAccountingUtil::class)->applyVasLocale($businessId, request());
                    $view->with('vasAccountingLocale', $locale);
                }
            }

            if (! auth()->check() || ! auth()->user()->can('vas_accounting.access')) {
                $view->with('vasAccountingNavConfig', null);
                $view->with('vasAccountingPageMeta', null);
                $view->with('vasAccountingBusinessContext', null);
                $view->with('vasAccountingCurrentPeriod', null);

                return;
            }

            if (! Str::startsWith($routeName, 'vasaccounting.')) {
                $view->with('vasAccountingNavConfig', null);
                $view->with('vasAccountingPageMeta', null);
                $view->with('vasAccountingBusinessContext', null);
                $view->with('vasAccountingCurrentPeriod', null);

                return;
            }

            $businessId = (int) request()->session()->get('user.business_id');
            if ($businessId <= 0) {
                $view->with('vasAccountingNavConfig', null);
                $view->with('vasAccountingPageMeta', null);
                $view->with('vasAccountingBusinessContext', null);
                $view->with('vasAccountingCurrentPeriod', null);

                return;
            }

            $vasUtil = $this->app->make(VasAccountingUtil::class);
            $settings = $vasUtil->getOrCreateBusinessSettings($businessId);
            $view->with('vasAccountingNavConfig', [
                'business_id' => $businessId,
                'report_route_prefix' => (string) config('vasaccounting.report_route_prefix'),
                'feature_flags' => array_replace($vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags),
                'navigation_items' => $vasUtil->navigationItems($businessId),
                'navigation_groups' => $vasUtil->navigationGroups($businessId),
            ]);
            $view->with('vasAccountingPageMeta', $vasUtil->pageMeta($routeName, $businessId));
            $view->with('vasAccountingBusinessContext', $vasUtil->businessContext($businessId));
            $view->with('vasAccountingCurrentPeriod', $vasUtil->currentPeriodContext($businessId));
        });
    }

    protected function transactionSnapshot(Transaction $transaction): array
    {
        return [
            'id' => (int) $transaction->id,
            'business_id' => (int) $transaction->business_id,
            'location_id' => (int) ($transaction->location_id ?? 0),
            'contact_id' => (int) ($transaction->contact_id ?? 0),
            'transaction_date' => $transaction->transaction_date,
            'ref_no' => $transaction->ref_no,
            'invoice_no' => $transaction->invoice_no,
            'status' => (string) ($transaction->status ?? ''),
            'type' => (string) ($transaction->type ?? ''),
            'final_total' => (float) ($transaction->final_total ?? 0),
            'tax_amount' => (float) ($transaction->tax_amount ?? 0),
            'created_by' => (int) ($transaction->created_by ?? 0),
        ];
    }

    protected function paymentSnapshot($payment): array
    {
        return [
            'id' => (int) ($payment->id ?? 0),
            'transaction_id' => (int) ($payment->transaction_id ?? 0),
            'amount' => (float) ($payment->amount ?? 0),
            'method' => $payment->method ?? null,
            'paid_on' => $payment->paid_on ?? null,
            'payment_ref_no' => $payment->payment_ref_no ?? null,
            'transaction_no' => $payment->transaction_no ?? null,
            'created_by' => (int) ($payment->created_by ?? 0),
        ];
    }
}
