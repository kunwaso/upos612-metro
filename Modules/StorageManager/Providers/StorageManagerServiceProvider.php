<?php

namespace Modules\StorageManager\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\StorageManager\Http\ViewComposers\StorageManagerToolbarViewComposer;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;
use Modules\StorageManager\Console\AuditLotExpiryReadinessCommand;
use Modules\StorageManager\Console\ReconcileWarehouseLocationCommand;
use Modules\StorageManager\Services\CycleCountService;
use Modules\StorageManager\Services\DamageQuarantineService;
use Modules\StorageManager\Services\InventoryMovementService;
use Modules\StorageManager\Services\OutboundExecutionService;
use Modules\StorageManager\Services\PurchasingAdvisoryService;
use Modules\StorageManager\Services\PutawayService;
use Modules\StorageManager\Services\ReconciliationService;
use Modules\StorageManager\Services\ReplenishmentService;
use Modules\StorageManager\Services\ReceivingService;
use Modules\StorageManager\Services\StockAdjustmentBridgeService;
use Modules\StorageManager\Services\SourceDocumentAdapterManager;
use Modules\StorageManager\Services\TransferExecutionService;
use Modules\StorageManager\Services\WarehouseKpiService;
use Modules\StorageManager\Services\WarehouseSyncService;

class StorageManagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        View::composer('storagemanager::*', StorageManagerToolbarViewComposer::class);
        Blade::componentNamespace('Modules\\StorageManager\\View\\Components', 'storagemanager');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReconcileWarehouseLocationCommand::class,
                AuditLotExpiryReadinessCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton(StorageManagerToolbarNavUtil::class);

        $this->app->register(RouteServiceProvider::class);
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'storagemanager');
        $this->app->singleton(CycleCountService::class);
        $this->app->singleton(DamageQuarantineService::class);
        $this->app->singleton(InventoryMovementService::class);
        $this->app->singleton(OutboundExecutionService::class);
        $this->app->singleton(PurchasingAdvisoryService::class);
        $this->app->singleton(PutawayService::class);
        $this->app->singleton(ReconciliationService::class);
        $this->app->singleton(ReplenishmentService::class);
        $this->app->singleton(ReceivingService::class);
        $this->app->singleton(StockAdjustmentBridgeService::class);
        $this->app->singleton(SourceDocumentAdapterManager::class);
        $this->app->singleton(TransferExecutionService::class);
        $this->app->singleton(WarehouseKpiService::class);
        $this->app->singleton(WarehouseSyncService::class);
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('storagemanager.php'),
        ], 'config');
    }

    protected function registerViews()
    {
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => resource_path('views/modules/storagemanager'),
        ], 'views');

        $this->loadViewsFrom([$sourcePath], 'storagemanager');
    }

    public function provides()
    {
        return [];
    }
}
