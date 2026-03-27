<?php

namespace Modules\Mailbox\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Mailbox\Console\Commands\SyncMailboxCommand;

class MailboxServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Mailbox';

    protected $moduleNameLower = 'mailbox';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerAssets();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
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
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath, $this->moduleNameLower);

            return;
        }

        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
    }

    protected function registerAssets(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Resources/assets') => public_path('modules/' . $this->moduleNameLower),
        ], $this->moduleNameLower . '-assets');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            SyncMailboxCommand::class,
        ]);
    }

    protected function registerScheduleCommands(): void
    {
        $this->app->booted(function () {
            if (! config('mailbox.sync.enabled', true)) {
                return;
            }

            $minutes = max(1, (int) config('mailbox.sync.interval_minutes', 5));
            $schedule = $this->app->make(Schedule::class);

            if ($minutes === 5) {
                $schedule->command('mailbox:sync')->everyFiveMinutes();

                return;
            }

            $schedule->command('mailbox:sync')->cron('*/' . min(59, $minutes) . ' * * * *');
        });
    }

    protected function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        return $paths;
    }
}
