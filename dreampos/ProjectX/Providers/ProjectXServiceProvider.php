<?php

namespace Modules\ProjectX\Providers;

use App\BusinessLocation;
use App\Contact;
use App\Utils\ModuleUtil;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\ProjectX\Console\Commands\EncryptChatMemoryCommand;
use Modules\ProjectX\Console\Commands\PruneChatConversationsCommand;
use Modules\ProjectX\Contracts\QuoteMailerInterface;
use Modules\ProjectX\Entities\FabricActivityLog;
use Modules\ProjectX\Entities\Quote;
use Modules\ProjectX\Services\QuoteMailerLaravel;
use Modules\ProjectX\Services\QuoteMailerStub;
use Modules\ProjectX\Utils\ChatUtil;
use Modules\ProjectX\Utils\FabricActivityLogUtil;
use Modules\ProjectX\Utils\FabricCostingUtil;
use Modules\ProjectX\Utils\FabricManagerUtil;
use Modules\ProjectX\Utils\FabricProductSyncUtil;
use Modules\ProjectX\Utils\ProjectXNumberFormatUtil;
use Modules\ProjectX\Utils\ProjectXQuoteDisplayPresenter;

class ProjectXServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerAssets();
        $this->registerWelcomeControllerPublish();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerProjectXNumberFormatComposer();
        $this->registerChatViewComposer();
        $this->registerProductViewComposer();
        $this->registerProductDetailViewComposer();
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->registerQuoteMailer();
        $this->registerCommands();
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('projectx.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'projectx'
        );
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/projectx');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $paths = array_merge(
            array_map(fn ($path) => $path . '/modules/projectx', config('view.paths')),
            [$sourcePath]
        );
        $this->loadViewsFrom(array_values(array_filter($paths, 'is_dir')), 'projectx');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/projectx');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'projectx');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'projectx');
        }
    }

    protected function registerAssets()
    {
        $this->publishes([
            __DIR__ . '/../Resources/assets' => public_path('modules/projectx'),
        ], 'projectx-assets');
    }

    /**
     * Publish WelcomeController stub so that copying ProjectX to a new site can enable
     * the Site Manager welcome page override by publishing this file into the app.
     */
    protected function registerWelcomeControllerPublish(): void
    {
        $this->publishes([
            __DIR__ . '/../Resources/stubs/WelcomeController.php' => app_path('Http/Controllers/WelcomeController.php'),
        ], 'projectx-welcome');
    }

    public function provides()
    {
        return [];
    }

    protected function registerQuoteMailer()
    {
        $this->app->bind(QuoteMailerInterface::class, function ($app) {
            $driver = config('projectx.quote_mailer.driver', 'stub');

            if ($driver === 'laravel') {
                return $app->make(QuoteMailerLaravel::class);
            }

            return $app->make(QuoteMailerStub::class);
        });
    }

    protected function registerCommands()
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            PruneChatConversationsCommand::class,
            EncryptChatMemoryCommand::class,
        ]);
    }

    protected function registerChatViewComposer()
    {
        View::composer('projectx::layouts.main', function ($view) {
            try {
                if (! auth()->check()) {
                    $view->with('aiChatConfig', null);

                    return;
                }

                if (! auth()->user()->can('projectx.chat.view')) {
                    $view->with('aiChatConfig', null);

                    return;
                }

                $business_id = (int) session('user.business_id');
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

    protected function registerProductViewComposer(): void
    {
        View::composer(['product.create', 'product.edit'], function ($view) {
            $view_data = [
                'projectx_enabled' => false,
                'can_create_as_fabric' => false,
                'has_fabric_link' => false,
                'linked_fabric_id' => null,
                'linked_variation_id' => null,
                'linked_fabric_has_pantone' => false,
            ];

            try {
                if (! auth()->check()) {
                    $view->with($view_data);

                    return;
                }

                $module_util = app(ModuleUtil::class);
                if (! $module_util->isModuleInstalled('ProjectX') || ! class_exists(FabricProductSyncUtil::class)) {
                    $view->with($view_data);

                    return;
                }

                $view_data['projectx_enabled'] = true;
                $view_data['can_create_as_fabric'] = auth()->user()->can('projectx.fabric.create');

                if ($view->name() === 'product.edit') {
                    $product = $view->getData()['product'] ?? null;
                    $business_id = (int) session('user.business_id');

                    if (! empty($product) && ! empty($product->id) && $business_id > 0) {
                        /** @var FabricProductSyncUtil $fabric_sync_util */
                        $fabric_sync_util = app(FabricProductSyncUtil::class);
                        $linked_fabric = $fabric_sync_util->findLinkedFabric($business_id, (int) $product->id);

                        if (! empty($linked_fabric)) {
                            $view_data['has_fabric_link'] = true;
                            $view_data['linked_fabric_id'] = (int) $linked_fabric->id;
                            $view_data['linked_variation_id'] = ! empty($linked_fabric->variation_id) ? (int) $linked_fabric->variation_id : null;
                            $view_data['linked_fabric_has_pantone'] = (bool) $linked_fabric->pantoneItems->isNotEmpty();
                        }
                    }
                }
            } catch (\Throwable $exception) {
                // Keep defaults to avoid breaking core product screens.
            }

            $view->with($view_data);
        });
    }

    protected function registerProductDetailViewComposer(): void
    {
        View::composer('product.detail', function ($view) {
            $view_data = [
                'projectx_on_product_detail' => false,
                'projectx_linked_fabric' => null,
                'projectx_product_detail_tab' => null,
                'projectx_quotes_customers_dropdown' => [],
                'projectx_quotes_locations_dropdown' => [],
                'projectx_quotes_costing_dropdowns' => [
                    'purchase_uom' => [],
                    'currency' => [],
                    'incoterm' => [],
                ],
                'projectx_quotes_default_currency_code' => null,
                'projectx_quotes_default_base_price' => 0,
                'projectx_quotes_default_base_price_input' => '0.00',
                'projectx_quotes_latest_quote' => null,
                'projectx_quotes_latest_quote_line' => null,
                'projectx_quotes_latest_quote_summary' => null,
                'projectx_quotes_latest_quote_recipient_email' => '',
                'projectx_activity_today' => collect(),
                'projectx_activity_week' => collect(),
                'projectx_activity_month' => collect(),
                'projectx_activity_year' => collect(),
                'projectx_activity_year_label' => (int) now()->year,
                'projectx_activity_can_delete' => false,
                'projectx_files_attachments' => [],
                'projectx_files_upload_url' => null,
                'projectx_files_can_manage' => false,
                'projectx_contacts_customers_dropdown' => [],
            ];

            try {
                if (! auth()->check()) {
                    $view->with($view_data);

                    return;
                }

                $business_id = (int) session('user.business_id');
                $product = $view->getData()['product'] ?? null;
                if ($business_id <= 0 || empty($product) || empty($product->id)) {
                    $view->with($view_data);

                    return;
                }

                $activeTab = (string) ($view->getData()['activeTab'] ?? request('tab', 'overview'));
                $normalizedTab = strtolower(trim($activeTab));
                if ($normalizedTab === 'budget') {
                    $normalizedTab = 'quotes';
                }
                $view_data['projectx_product_detail_tab'] = $normalizedTab;

                /** @var FabricProductSyncUtil $fabric_sync_util */
                $fabric_sync_util = app(FabricProductSyncUtil::class);
                $linked_fabric = $fabric_sync_util->findLinkedFabric($business_id, (int) $product->id);
                if (empty($linked_fabric)) {
                    $view->with($view_data);

                    return;
                }

                $view_data['projectx_on_product_detail'] = true;
                $view_data['projectx_linked_fabric'] = $linked_fabric;
                $view_data['projectx_files_can_manage'] = auth()->user()->can('projectx.fabric.create')
                    || auth()->user()->can('product.create');

                $needsCustomerDropdown = in_array($normalizedTab, ['quotes', 'contacts'], true);
                if ($needsCustomerDropdown) {
                    $customersDropdown = Contact::customersDropdown($business_id, false, true);
                    $view_data['projectx_quotes_customers_dropdown'] = $customersDropdown;
                    $view_data['projectx_contacts_customers_dropdown'] = $customersDropdown;
                }

                if ($normalizedTab === 'quotes') {
                    /** @var FabricCostingUtil $fabric_costing_util */
                    $fabric_costing_util = app(FabricCostingUtil::class);
                    /** @var ProjectXQuoteDisplayPresenter $quote_display_presenter */
                    $quote_display_presenter = app(ProjectXQuoteDisplayPresenter::class);
                    /** @var ProjectXNumberFormatUtil $number_format_util */
                    $number_format_util = app(ProjectXNumberFormatUtil::class);

                    $locationsDropdown = BusinessLocation::forDropdown($business_id, false, false);
                    $costingDropdowns = $fabric_costing_util->getDropdownOptions($business_id);
                    $defaultCurrencyCode = $fabric_costing_util->getDefaultCurrencyCode($business_id);
                    $defaultBasePrice = (float) ($linked_fabric->price_500_yds ?? 0);

                    $business = null;
                    $sessionBusiness = session('business');
                    if (is_object($sessionBusiness)) {
                        $business = $sessionBusiness;
                    } elseif (is_array($sessionBusiness)) {
                        $business = (object) $sessionBusiness;
                    }

                    $defaultBasePriceInput = $number_format_util->formatInput(
                        $defaultBasePrice,
                        $number_format_util->getCurrencyPrecision($business)
                    );

                    $quoteQuery = Quote::forBusiness($business_id)
                        ->whereHas('lines', function ($query) use ($linked_fabric) {
                            $query->where('fabric_id', (int) $linked_fabric->id);
                        })
                        ->with([
                            'contact:id,name,supplier_business_name,email',
                            'location:id,name',
                            'transaction:id,invoice_no,status',
                            'lines' => function ($query) {
                                $query->orderBy('sort_order')->orderBy('id');
                            },
                            'lines.fabric:id,name,fabric_sku,mill_article_no',
                        ])
                        ->orderByDesc('id');

                    $selectedQuoteId = (int) request()->query('quote_id', 0);
                    $selectedQuote = null;
                    if ($selectedQuoteId > 0) {
                        $selectedQuote = (clone $quoteQuery)
                            ->where('projectx_quotes.id', $selectedQuoteId)
                            ->first();
                    }

                    $latestQuote = $selectedQuote ?: (clone $quoteQuery)->first();
                    $latestQuoteLine = null;
                    if ($latestQuote) {
                        $latestQuoteLine = $latestQuote->lines->firstWhere('fabric_id', (int) $linked_fabric->id)
                            ?: $latestQuote->lines->first();
                    }

                    $latestQuoteSummary = $quote_display_presenter->presentLatestQuoteSummary($latestQuote, $latestQuoteLine);
                    $latestQuoteRecipientEmail = (string) ($latestQuote
                        ? ($latestQuote->customer_email ?: (optional($latestQuote->contact)->email ?? ''))
                        : '');

                    $view_data['projectx_quotes_locations_dropdown'] = $locationsDropdown;
                    $view_data['projectx_quotes_costing_dropdowns'] = $costingDropdowns;
                    $view_data['projectx_quotes_default_currency_code'] = $defaultCurrencyCode;
                    $view_data['projectx_quotes_default_base_price'] = $defaultBasePrice;
                    $view_data['projectx_quotes_default_base_price_input'] = $defaultBasePriceInput;
                    $view_data['projectx_quotes_latest_quote'] = $latestQuote;
                    $view_data['projectx_quotes_latest_quote_line'] = $latestQuoteLine;
                    $view_data['projectx_quotes_latest_quote_summary'] = $latestQuoteSummary;
                    $view_data['projectx_quotes_latest_quote_recipient_email'] = $latestQuoteRecipientEmail;
                    $view_data = array_merge($view_data, $number_format_util->buildViewPayload($business));
                }

                if ($normalizedTab === 'activity') {
                    /** @var FabricActivityLogUtil $activity_log_util */
                    $activity_log_util = app(FabricActivityLogUtil::class);
                    $fabric_id = (int) $linked_fabric->id;

                    $view_data['projectx_activity_today'] = $activity_log_util->getForFabric($business_id, $fabric_id, FabricActivityLog::PERIOD_TODAY);
                    $view_data['projectx_activity_week'] = $activity_log_util->getForFabric($business_id, $fabric_id, FabricActivityLog::PERIOD_WEEK);
                    $view_data['projectx_activity_month'] = $activity_log_util->getForFabric($business_id, $fabric_id, FabricActivityLog::PERIOD_MONTH);
                    $view_data['projectx_activity_year'] = $activity_log_util->getForFabric($business_id, $fabric_id, FabricActivityLog::PERIOD_YEAR);
                    $view_data['projectx_activity_year_label'] = (int) now()->year;
                    $view_data['projectx_activity_can_delete'] = auth()->user()->can('superadmin')
                        || auth()->user()->can('projectx.fabric.activity.delete');
                }

                if ($normalizedTab === 'files') {
                    /** @var FabricManagerUtil $fabric_manager_util */
                    $fabric_manager_util = app(FabricManagerUtil::class);

                    $attachments = $fabric_manager_util->getAttachmentListForFabric($business_id, (int) $linked_fabric->id);
                    $view_data['projectx_files_upload_url'] = route('projectx.fabric_manager.files.upload', ['fabric_id' => (int) $linked_fabric->id]);
                    $view_data['projectx_files_attachments'] = array_map(function ($attachment) use ($linked_fabric) {
                        return [
                            'hash' => (string) ($attachment['hash'] ?? ''),
                            'name' => (string) ($attachment['name'] ?? ''),
                            'extension' => (string) ($attachment['extension'] ?? ''),
                            'size_bytes' => (int) ($attachment['size_bytes'] ?? 0),
                            'size_display' => (string) ($attachment['size_display'] ?? '0 B'),
                            'download_url' => route('projectx.fabric_manager.files.download', [
                                'fabric_id' => (int) $linked_fabric->id,
                                'file_hash' => (string) ($attachment['hash'] ?? ''),
                            ]),
                            'delete_url' => route('projectx.fabric_manager.files.delete', [
                                'fabric_id' => (int) $linked_fabric->id,
                                'file_hash' => (string) ($attachment['hash'] ?? ''),
                            ]),
                        ];
                    }, $attachments);
                }
            } catch (\Throwable $exception) {
                // Keep defaults to avoid breaking core product detail screens.
            }

            $view->with($view_data);
        });
    }

    protected function registerProjectXNumberFormatComposer(): void
    {
        View::composer('projectx::*', function ($view) {
            try {
                $viewData = (array) $view->getData();
                $business = null;

                if (isset($viewData['quote']) && is_object($viewData['quote'])) {
                    $business = data_get($viewData['quote'], 'business');
                }

                if (! is_object($business)) {
                    $sessionBusiness = session('business');
                    if (is_object($sessionBusiness)) {
                        $business = $sessionBusiness;
                    } elseif (is_array($sessionBusiness)) {
                        $business = (object) $sessionBusiness;
                    }
                }

                /** @var ProjectXNumberFormatUtil $numberFormatUtil */
                $numberFormatUtil = app(ProjectXNumberFormatUtil::class);
                $view->with($numberFormatUtil->buildViewPayload($business));
            } catch (\Throwable $exception) {
                $view->with([
                    'projectxCurrencyPrecision' => 2,
                    'projectxQuantityPrecision' => 2,
                    'projectxCurrencyStep' => '0.01',
                    'projectxQuantityStep' => '0.01',
                    'projectxRatePrecision' => 4,
                    'projectxRateStep' => '0.0001',
                    'projectxZeroMin' => '0',
                    'projectxPositiveQuantityMin' => '0.01',
                    'projectxCurrencySymbol' => '$',
                ]);
            }
        });
    }
}
