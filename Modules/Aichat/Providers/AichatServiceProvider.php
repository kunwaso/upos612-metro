<?php

namespace Modules\Aichat\Providers;

use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Aichat\Console\Commands\EncryptChatMemoryCommand;
use Modules\Aichat\Console\Commands\ExportChatAuditCommand;
use Modules\Aichat\Console\Commands\PruneChatConversationsCommand;
use Modules\Aichat\Utils\ChatUtil;

class AichatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerAssets();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerChatViewComposer();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerCommands();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('aichat.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'aichat');
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/aichat');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', 'aichat-module-views']);

        $paths = array_merge(
            array_map(function ($path) {
                return $path . '/modules/aichat';
            }, config('view.paths')),
            [$sourcePath]
        );

        $this->loadViewsFrom(array_values(array_filter($paths, 'is_dir')), 'aichat');
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/aichat');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'aichat');

            return;
        }

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'aichat');
    }

    protected function registerAssets(): void
    {
        $this->publishes([
            __DIR__ . '/../Resources/assets' => public_path('modules/aichat'),
        ], 'aichat-assets');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            PruneChatConversationsCommand::class,
            EncryptChatMemoryCommand::class,
            ExportChatAuditCommand::class,
        ]);
    }

    protected function registerChatViewComposer(): void
    {
        View::composer('layouts.app', function ($view) {
            try {
                if (! auth()->check()) {
                    $view->with('aiChatConfig', null);

                    return;
                }

                /** @var ModuleUtil $moduleUtil */
                $moduleUtil = app(ModuleUtil::class);
                if (! $moduleUtil->isModuleInstalled('Aichat')) {
                    $view->with('aiChatConfig', null);

                    return;
                }

                if (! auth()->user()->can('aichat.chat.view')) {
                    $view->with('aiChatConfig', null);

                    return;
                }

                $business_id = (int) request()->session()->get('user.business_id');
                if ($business_id <= 0) {
                    $view->with('aiChatConfig', null);

                    return;
                }

                /** @var ChatUtil $chatUtil */
                $chatUtil = app(ChatUtil::class);
                if (! $chatUtil->isChatEnabled($business_id)) {
                    $view->with('aiChatConfig', null);

                    return;
                }

                $view->with('aiChatConfig', $chatUtil->buildClientConfig($business_id, (int) auth()->id()));
            } catch (\Throwable $exception) {
                $view->with('aiChatConfig', null);
            }
        });
    }
}
