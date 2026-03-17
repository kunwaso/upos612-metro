<?php

namespace Modules\Projectauto\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Projectauto\Console\Commands\ProjectautoEscalationCommand;

class ProjectautoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerAssets();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerScheduleCommands();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerCommands();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('projectauto.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'projectauto');
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/projectauto');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', 'projectauto-module-views']);

        $paths = array_merge(
            array_map(function ($path) {
                return $path . '/modules/projectauto';
            }, config('view.paths')),
            [$sourcePath]
        );

        $this->loadViewsFrom(array_values(array_filter($paths, 'is_dir')), 'projectauto');
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/projectauto');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'projectauto');

            return;
        }

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'projectauto');
    }

    protected function registerAssets(): void
    {
        $this->publishes([
            __DIR__ . '/../Resources/assets' => public_path('modules/projectauto'),
        ], 'projectauto-assets');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ProjectautoEscalationCommand::class,
        ]);
    }

    protected function registerScheduleCommands(): void
    {
        if (config('app.env') !== 'live') {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('projectauto:escalate')->hourly();
        });
    }
}
