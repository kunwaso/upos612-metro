<?php

namespace App\Providers;

use App\Contracts\QuoteMailerInterface;
use App\Services\QuoteMailerLaravel;
use App\Services\QuoteMailerStub;
use App\System;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

use Laravel\Passport\Console\ClientCommand;
use Laravel\Passport\Console\InstallCommand;
use Laravel\Passport\Console\KeysCommand;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        if (config('app.debug')) {
            error_reporting(E_ALL & ~E_USER_DEPRECATED);
        } else {
            error_reporting(0);
        }

        //force https
        $url = parse_url(config('app.url'));

        if ($url['scheme'] == 'https') {
            \URL::forceScheme('https');
        }

        if (request()->has('lang')) {
            \App::setLocale(request()->get('lang'));
        }

        //In Laravel 5.6, Blade will double encode special characters by default. If you would like to maintain the previous behavior of preventing double encoding, you may add Blade::withoutDoubleEncoding() to your AppServiceProvider boot method.
        Blade::withoutDoubleEncoding();

        //Laravel 5.6 uses Bootstrap 4 by default. Shift did not update your front-end resources or dependencies as this could impact your UI. If you are using Bootstrap and wish to continue using Bootstrap 3, you should add Paginator::useBootstrapThree() to your AppServiceProvider boot method.
        Paginator::useBootstrapThree();

        \Illuminate\Pagination\Paginator::useBootstrap();

        // Dropbox service provider
        Storage::extend('dropbox', function ($app, $config) {
            $adapter = new DropboxAdapter(new DropboxClient(
                $config['authorization_token']
            ));
 
            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });


        $asset_v = config('constants.asset_version', 1);
        View::share('asset_v', $asset_v);

        // Share the list of modules enabled in sidebar
        View::composer(
            ['*'],
            function ($view) {
                $enabled_modules = ! empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];

                $__is_pusher_enabled = isPusherEnabled();

                if (! Auth::check()) {
                    $__is_pusher_enabled = false;
                }

                $view->with('enabled_modules', $enabled_modules);
                $view->with('__is_pusher_enabled', $__is_pusher_enabled);
            }
        );

        View::composer(
            ['layouts.*'],
            function ($view) {
                if (isAppInstalled()) {
                    $is_org_admin = false;
                    if (Auth::check() && session()->has('business.id')) {
                        $is_org_admin = (new Util)->is_admin(Auth::user(), session('business.id'));
                    }

                    $keys = ['additional_js', 'additional_css'];
                    $__system_settings = System::getProperties($keys, true);

                    //Get js,css from modules
                    $moduleUtil = new ModuleUtil;
                    $module_additional_script = $moduleUtil->getModuleData('get_additional_script');
                    $additional_views = [];
                    $additional_html = '';
                    foreach ($module_additional_script as $key => $value) {
                        if (! empty($value['additional_js'])) {
                            if (isset($__system_settings['additional_js'])) {
                                $__system_settings['additional_js'] .= $value['additional_js'];
                            } else {
                                $__system_settings['additional_js'] = $value['additional_js'];
                            }
                        }
                        if (! empty($value['additional_css'])) {
                            if (isset($__system_settings['additional_css'])) {
                                $__system_settings['additional_css'] .= $value['additional_css'];
                            } else {
                                $__system_settings['additional_css'] = $value['additional_css'];
                            }
                        }
                        if (! empty($value['additional_html'])) {
                            $additional_html .= $value['additional_html'];
                        }
                        if (! empty($value['additional_views'])) {
                            $additional_views = array_merge($additional_views, $value['additional_views']);
                        }
                    }

                    $view->with('__additional_views', $additional_views);
                    $view->with('__additional_html', $additional_html);
                    $view->with('__system_settings', $__system_settings);
                    $view->with('is_org_admin', $is_org_admin);

                    $globalSearchTypes = [];
                    if (Auth::check()) {
                        $user = Auth::user();
                        $canSearchCustomers = $user->can('customer.view') || $user->can('customer.view_own');
                        $canSearchSuppliers = $user->can('supplier.view') || $user->can('supplier.view_own');

                        if ($canSearchCustomers || $canSearchSuppliers) {
                            $globalSearchTypes['contacts'] = [
                                'label' => 'Contacts',
                                'url' => route('global-search.contacts'),
                                'param' => 'q',
                                'params' => ['type' => 'both'],
                            ];
                        }

                        if ($canSearchCustomers) {
                            $globalSearchTypes['customers'] = [
                                'label' => 'Customers',
                                'url' => route('global-search.contacts'),
                                'param' => 'q',
                                'params' => ['type' => 'customer'],
                            ];
                        }

                        if ($canSearchSuppliers) {
                            $globalSearchTypes['suppliers'] = [
                                'label' => 'Suppliers',
                                'url' => route('global-search.contacts'),
                                'param' => 'q',
                                'params' => ['type' => 'supplier'],
                            ];
                        }

                        if ($user->can('product.view')) {
                            $globalSearchTypes['products'] = [
                                'label' => 'Products',
                                'url' => route('global-search.products'),
                                'param' => 'q',
                                'params' => [],
                            ];
                        }

                        if ($user->can('sell.view') || $user->can('direct_sell.view')) {
                            $globalSearchTypes['sales_orders'] = [
                                'label' => 'Sales Orders',
                                'url' => route('global-search.sales-orders'),
                                'param' => 'q',
                                'params' => [],
                            ];
                        }

                        if ($user->can('purchase.update')) {
                            $globalSearchTypes['purchases'] = [
                                'label' => 'Purchases',
                                'url' => route('global-search.purchases'),
                                'param' => 'q',
                                'params' => [],
                            ];
                        }
                    }

                    $view->with('globalSearchConfig', [
                        'defaultType' => array_key_first($globalSearchTypes),
                        'types' => $globalSearchTypes,
                    ]);
                }
            }
        );

        //This will fix "Specified key was too long; max key length is 767 bytes issue during migration"
        Schema::defaultStringLength(191);

        //Blade directive to format number into required format.
        Blade::directive('num_format', function ($expression) {
            return "<?php echo number_format((float) ($expression), session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']); ?>";
        });

        //Blade directive to format quantity values into required format.
        Blade::directive('format_quantity', function ($expression) {
            return "<?php echo number_format((float) ($expression), session('business.quantity_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']); ?>";
        });

        //Blade directive to return appropiate class according to transaction status
        Blade::directive('transaction_status', function ($status) {
            return "<?php if($status == 'ordered'){
                echo 'bg-aqua';
            }elseif($status == 'pending'){
                echo 'bg-red';
            }elseif ($status == 'received') {
                echo 'bg-light-green';
            }?>";
        });

        //Blade directive to return appropiate class according to transaction status
        Blade::directive('payment_status', function ($status) {
            return "<?php if($status == 'partial'){
                echo 'bg-aqua';
            }elseif($status == 'due'){
                echo 'bg-yellow';
            }elseif ($status == 'paid') {
                echo 'bg-light-green';
            }elseif ($status == 'overdue') {
                echo 'bg-red';
            }elseif ($status == 'partial-overdue') {
                echo 'bg-red';
            }?>";
        });

        //Blade directive to display help text.
        Blade::directive('show_tooltip', function ($message) {
            return "<?php
                if(session('business.enable_tooltip')){
                    echo '<i class=\"fa fa-info-circle text-info hover-q no-print \" aria-hidden=\"true\" 
                    data-container=\"body\" data-toggle=\"popover\" data-placement=\"auto bottom\" 
                    data-content=\"' . $message . '\" data-html=\"true\" data-trigger=\"hover\"></i>';
                }
                ?>";
        });

        //Blade directive to convert.
        Blade::directive('format_date', function ($date) {
            if (! empty($date)) {
                return "\Carbon::createFromTimestamp(strtotime($date))->format(session('business.date_format'))";
            } else {
                return null;
            }
        });

        //Blade directive to convert.
        Blade::directive('format_time', function ($date) {
            if (! empty($date)) {
                $time_format = 'h:i A';
                if (session('business.time_format') == 24) {
                    $time_format = 'H:i';
                }

                return "\Carbon::createFromTimestamp(strtotime($date))->format('$time_format')";
            } else {
                return null;
            }
        });

        Blade::directive('format_datetime', function ($date) {
            if (! empty($date)) {
                $time_format = 'h:i A';
                if (session('business.time_format') == 24) {
                    $time_format = 'H:i';
                }

                return "\Carbon::createFromTimestamp(strtotime($date))->format(session('business.date_format') . ' ' . '$time_format')";
            } else {
                return null;
            }
        });

        //Blade directive to format currency.
        Blade::directive('format_currency', function ($number) {
            return '<?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) '.$number.', session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>';
        });

        $this->registerCommands();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(QuoteMailerInterface::class, function ($app) {
            $driver = (string) config('product.quote_mailer_driver', 'laravel');

            if ($driver === 'stub') {
                return $app->make(QuoteMailerStub::class);
            }

            return $app->make(QuoteMailerLaravel::class);
        });
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            InstallCommand::class,
            ClientCommand::class,
            KeysCommand::class,
        ]);
    }
}
