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
use Modules\VasAccounting\Console\ClosePeriodCommand;
use Modules\VasAccounting\Console\ReplayPostingsCommand;
use Modules\VasAccounting\Console\RunDepreciationCommand;
use Modules\VasAccounting\Console\SeedVnChartCommand;
use Modules\VasAccounting\Contracts\EInvoiceAdapterInterface;
use Modules\VasAccounting\Services\BudgetControlService;
use Modules\VasAccounting\Services\ContractAccountingService;
use Modules\VasAccounting\Services\CutoverService;
use Modules\VasAccounting\Services\Adapters\SandboxEInvoiceAdapter;
use Modules\VasAccounting\Services\BankStatementImportAdapterManager;
use Modules\VasAccounting\Services\EnterpriseReportingService;
use Modules\VasAccounting\Services\EInvoiceAdapterManager;
use Modules\VasAccounting\Services\IntegrationHubService;
use Modules\VasAccounting\Services\LoanAccountingService;
use Modules\VasAccounting\Services\PayrollBridgeManager;
use Modules\VasAccounting\Services\ReportSnapshotService;
use Modules\VasAccounting\Services\SourceDocumentAdapterManager;
use Modules\VasAccounting\Services\TaxExportAdapterManager;
use Modules\VasAccounting\Services\VasDepreciationService;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Services\VasPeriodCloseService;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Services\VasPayrollBridgeService;
use Modules\VasAccounting\Services\VasToolAmortizationService;
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
        $this->app->singleton(CutoverService::class);
        $this->app->singleton(OperationsAssetReportUtil::class);
        $this->app->singleton(EnterprisePlanningReportUtil::class);
        $this->app->singleton(EnterpriseReportingService::class);
        $this->app->singleton(ReportSnapshotService::class);
        $this->app->singleton(IntegrationHubService::class);
        $this->app->bind(EInvoiceAdapterInterface::class, SandboxEInvoiceAdapter::class);
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
        ]);
    }

    protected function registerEventListeners(): void
    {
        Event::listen(SellCreatedOrModified::class, function (SellCreatedOrModified $event) {
            app(VasPostingService::class)->queueSourceDocument('sell', $event->transaction);
        });

        Event::listen(PurchaseCreatedOrModified::class, function (PurchaseCreatedOrModified $event) {
            app(VasPostingService::class)->queueSourceDocument('purchase', $event->transaction, [
                'is_deleted' => (bool) $event->isDeleted,
            ]);
        });

        Event::listen(ExpenseCreatedOrModified::class, function (ExpenseCreatedOrModified $event) {
            app(VasPostingService::class)->queueSourceDocument('expense', $event->expense, [
                'is_deleted' => (bool) $event->isDeleted,
            ]);
        });

        Event::listen(StockAdjustmentCreatedOrModified::class, function (StockAdjustmentCreatedOrModified $event) {
            app(VasPostingService::class)->queueSourceDocument('stock_adjustment', $event->stockAdjustment, [
                'action' => (string) $event->action,
            ]);
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
            app(VasPostingService::class)->queueSourceDocument('transaction_payment', $event->transactionPayment, [
                'is_deleted' => (bool) $event->isDeleted,
            ]);
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
        View::composer('layouts.app', function ($view) {
            if (! auth()->check() || ! auth()->user()->can('vas_accounting.access')) {
                $view->with('vasAccountingNavConfig', null);

                return;
            }

            $businessId = (int) request()->session()->get('user.business_id');
            if ($businessId <= 0) {
                $view->with('vasAccountingNavConfig', null);

                return;
            }

            $view->with('vasAccountingNavConfig', [
                'business_id' => $businessId,
                'report_route_prefix' => (string) config('vasaccounting.report_route_prefix'),
                'feature_flags' => (array) $this->app->make(VasAccountingUtil::class)->getOrCreateBusinessSettings($businessId)->feature_flags,
                'navigation_items' => $this->app->make(VasAccountingUtil::class)->navigationItems($businessId),
            ]);
        });
    }
}
