<?php

namespace Modules\StorageManager\Providers;

use Illuminate\Support\ServiceProvider;

class StorageManagerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'storagemanager');
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
