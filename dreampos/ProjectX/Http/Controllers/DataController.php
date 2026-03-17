<?php

namespace Modules\ProjectX\Http\Controllers;

use App\Product;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Menu;
use Modules\ProjectX\Utils\FabricLinkSelectorResolver;
use Modules\ProjectX\Utils\FabricProductSyncUtil;
use Modules\ProjectX\Utils\QuoteUtil;

class DataController extends Controller
{
    /**
     * Defines user permissions for the module.
     *
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'projectx.fabric.view',
                'label' => __('projectx::lang.permission_fabric_view'),
                'default' => false,
            ],
            [
                'value' => 'projectx.fabric.create',
                'label' => __('projectx::lang.permission_fabric_create'),
                'default' => false,
            ],
            [
                'value' => 'projectx.fabric.activity.delete',
                'label' => __('projectx::lang.permission_fabric_activity_delete'),
                'default' => false,
            ],
            [
                'value' => 'projectx.trim.view',
                'label' => __('projectx::lang.permission_trim_view'),
                'default' => false,
            ],
            [
                'value' => 'projectx.trim.create',
                'label' => __('projectx::lang.permission_trim_create'),
                'default' => false,
            ],
            [
                'value' => 'projectx.trim.edit',
                'label' => __('projectx::lang.permission_trim_edit'),
                'default' => false,
            ],
            [
                'value' => 'projectx.trim.delete',
                'label' => __('projectx::lang.permission_trim_delete'),
                'default' => false,
            ],
            [
                'value' => 'projectx.quote.view',
                'label' => __('projectx::lang.permission_quote_view'),
                'default' => false,
            ],
            [
                'value' => 'projectx.quote.create',
                'label' => __('projectx::lang.permission_quote_create'),
                'default' => false,
            ],
            [
                'value' => 'projectx.quote.send',
                'label' => __('projectx::lang.permission_quote_send'),
                'default' => false,
            ],
            [
                'value' => 'projectx.quote.edit',
                'label' => __('projectx::lang.permission_quote_edit'),
                'default' => false,
            ],
            [
                'value' => 'projectx.quote.delete',
                'label' => __('projectx::lang.permission_quote_delete'),
                'default' => false,
            ],
            [
                'value' => 'projectx.quote.admin_override',
                'label' => __('projectx::lang.permission_quote_admin_override'),
                'default' => false,
            ],
            [
                'value' => 'projectx.quote.release_invoice',
                'label' => __('projectx::lang.permission_quote_release_invoice'),
                'default' => false,
            ],
            [
                'value' => 'projectx.sales_order.edit',
                'label' => __('projectx::lang.permission_sales_order_edit'),
                'default' => false,
            ],
            [
                'value' => 'projectx.sales_order.update_status',
                'label' => __('projectx::lang.permission_sales_order_update_status'),
                'default' => false,
            ],
            [
                'value' => 'projectx.chat.view',
                'label' => __('projectx::lang.permission_chat_view'),
                'default' => false,
            ],
            [
                'value' => 'projectx.chat.edit',
                'label' => __('projectx::lang.permission_chat_edit'),
                'default' => false,
            ],
            [
                'value' => 'projectx.chat.settings',
                'label' => __('projectx::lang.permission_chat_settings'),
                'default' => false,
            ],
            [
                'value' => 'projectx.site_manager.edit',
                'label' => __('projectx::lang.permission_site_manager_edit'),
                'default' => false,
            ],
        ];
    }

    /**
     * Provide the welcome (landing) view when Site Manager is used. Called by root via getModuleData('welcome_view').
     *
     * @return array{name: string, data: array}
     */
    public function welcome_view()
    {
        $util = app(\Modules\ProjectX\Utils\SiteManagerUtil::class);
        $data = $util->getWelcomeViewData(null);

        return [
            'name' => 'projectx::site_manager.welcome',
            'data' => $data,
        ];
    }

    /**
     * Provide module auth views for core auth endpoints.
     *
     * @param  array{type?: string, data?: array}  $arguments
     * @return array{name: string, data: array}
     */
    public function auth_view(array $arguments)
    {
        $type = (string) ($arguments['type'] ?? '');
        $data = (isset($arguments['data']) && is_array($arguments['data'])) ? $arguments['data'] : [];

        $view_map = [
            'login' => 'projectx::site_manager.auth.login',
            'forgot' => 'projectx::site_manager.auth.forgot',
            'reset' => 'projectx::site_manager.auth.reset',
            'register' => 'projectx::site_manager.auth.register',
            'lock-screen' => 'projectx::site_manager.auth.lock-screen',
            'logout' => 'projectx::site_manager.auth.logout',
        ];

        if (! isset($view_map[$type])) {
            return [
                'name' => '',
                'data' => $data,
            ];
        }

        return [
            'name' => $view_map[$type],
            'data' => $data,
        ];
    }

    public function after_product_saved(array $arguments)
    {
        try {
            if (! $this->isProjectxInstalled() || ! class_exists(FabricProductSyncUtil::class)) {
                return null;
            }

            $product = $arguments['product'] ?? null;
            $request = $arguments['request'] ?? null;
            if (! $product instanceof Product || ! $request instanceof Request) {
                return null;
            }

            $business_id = (int) $request->session()->get('user.business_id');
            if ($business_id <= 0) {
                return null;
            }

            /** @var FabricProductSyncUtil $fabricProductSyncUtil */
            $fabricProductSyncUtil = app(FabricProductSyncUtil::class);
            /** @var FabricLinkSelectorResolver $fabricLinkSelectorResolver */
            $fabricLinkSelectorResolver = app(FabricLinkSelectorResolver::class);

            $fabric_link_selector = trim((string) $request->input('fabric_link_selector', ''));

            if (
                $request->boolean('create_as_fabric')
                && auth()->check()
                && auth()->user()->can('projectx.fabric.create')
            ) {
                $selected_variation_id = null;
                if ($product->type === 'variable') {
                    $selected_variation_id = $fabricLinkSelectorResolver->resolveCreateVariationIdFromSelector(
                        $product,
                        (array) $request->input('product_variation', []),
                        $fabric_link_selector
                    );
                }

                $fabricProductSyncUtil->createLinkedFabricFromProduct(
                    $business_id,
                    $product,
                    $selected_variation_id,
                    (int) $request->session()->get('user.id')
                );

                return null;
            }

            $linked_fabric = $fabricProductSyncUtil->findLinkedFabric($business_id, (int) $product->id);
            if (empty($linked_fabric)) {
                return null;
            }

            $selected_variation_id = null;
            if ($product->type === 'variable') {
                if ($fabric_link_selector === '') {
                    return null;
                }

                $selected_variation_id = $fabricLinkSelectorResolver->resolveExistingVariationIdFromSelector(
                    $product,
                    $fabric_link_selector
                );
            }

            $fabricProductSyncUtil->syncFabricFromProduct($business_id, $product, $selected_variation_id);
        } catch (\Throwable $exception) {
            \Log::warning('ProjectX after_product_saved failed: '.$exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
        }

        return null;
    }

    public function before_product_deleted(array $arguments)
    {
        if (! $this->isProjectxInstalled() || ! class_exists(FabricProductSyncUtil::class)) {
            return null;
        }

        $product = $arguments['product'] ?? null;
        $business_id = (int) ($arguments['business_id'] ?? 0);
        if (! $product instanceof Product || $business_id <= 0) {
            return null;
        }

        /** @var FabricProductSyncUtil $fabricProductSyncUtil */
        $fabricProductSyncUtil = app(FabricProductSyncUtil::class);
        $fabricProductSyncUtil->unlinkFabricForDeletedProduct($business_id, (int) $product->id);

        return null;
    }

    public function after_sale_saved(array $arguments)
    {
        if (! $this->isProjectxInstalled() || ! class_exists(QuoteUtil::class)) {
            return null;
        }

        $transaction = $arguments['transaction'] ?? null;
        if (empty($transaction) || empty($transaction->id)) {
            return null;
        }

        $input = (array) ($arguments['input'] ?? []);
        $quote_id = (int) ($input['projectx_quote_id'] ?? 0);
        if ($quote_id <= 0) {
            return null;
        }

        $business_id = (int) ($transaction->business_id ?? session('user.business_id'));
        if ($business_id <= 0) {
            return null;
        }

        /** @var QuoteUtil $quoteUtil */
        $quoteUtil = app(QuoteUtil::class);
        $quoteUtil->linkQuoteToTransaction($business_id, $quote_id, (int) $transaction->id);

        return null;
    }

    public function preferred_home_redirect()
    {
        if (! $this->isProjectxInstalled() || ! auth()->check() || ! \Route::has('projectx.index')) {
            return null;
        }

        if (! auth()->user()->can('product.view') && ! auth()->user()->can('sell.view')) {
            return null;
        }

        return [
            'url' => route('projectx.index'),
            'priority' => 100,
        ];
    }

    public function header_shortcuts()
    {
        if (! $this->isProjectxInstalled() || ! auth()->check() || ! \Route::has('projectx.index')) {
            return [];
        }

        if (! auth()->user()->can('product.view') && ! auth()->user()->can('sell.view')) {
            return [];
        }

        return [[
            'url' => route('projectx.index'),
            'title' => __('projectx::lang.dashboard'),
            'priority' => 100,
        ]];
    }

    public function modifyAdminMenu()
    {
        $module_util = new ModuleUtil();

        $is_projectx_installed = $module_util->isModuleInstalled('ProjectX');

        if ($is_projectx_installed) {
            if (auth()->user()->can('product.view') || auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view') || auth()->user()->can('projectx.fabric.view') || auth()->user()->can('projectx.trim.view') || auth()->user()->can('projectx.site_manager.edit')) {
                Menu::modify('admin-sidebar-menu', function ($menu) {
                    $menu->dropdown(
                        __('projectx::lang.projectx'),
                        function ($sub) {
                            $sub->url(
                                action([\Modules\ProjectX\Http\Controllers\DashboardController::class, 'index']),
                                __('projectx::lang.dashboard'),
                                ['icon' => '', 'active' => request()->segment(1) == 'projectx' && request()->segment(2) == '']
                            );
                            if (auth()->user()->can('product.view')) {
                                $sub->url(
                                    action([\Modules\ProjectX\Http\Controllers\ProductController::class, 'index']),
                                    __('projectx::lang.products'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'projectx' && request()->segment(2) == 'products']
                                );
                            }
                            if (auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view')) {
                                $sub->url(
                                    action([\Modules\ProjectX\Http\Controllers\SalesController::class, 'index']),
                                    __('projectx::lang.sales'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'projectx' && request()->segment(2) == 'sales']
                                );
                            }
                            if (auth()->user()->can('projectx.fabric.view')) {
                                $sub->url(
                                    action([\Modules\ProjectX\Http\Controllers\FabricManagerController::class, 'list']),
                                    __('projectx::lang.fabric_manager'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'projectx' && request()->segment(2) == 'fabric-manager']
                                );
                            }
                            if (auth()->user()->can('projectx.trim.view')) {
                                $sub->url(
                                    action([\Modules\ProjectX\Http\Controllers\TrimManagerController::class, 'list']),
                                    __('projectx::lang.trim_manager'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'projectx' && request()->segment(2) == 'trim-manager']
                                );
                            }
                            if (auth()->user()->can('projectx.site_manager.edit')) {
                                $sub->url(
                                    action([\Modules\ProjectX\Http\Controllers\SiteManagerController::class, 'index']),
                                    __('projectx::lang.site_manager'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'projectx' && request()->segment(2) == 'site-manager']
                                );
                            }
                        },
                        ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" /><path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" /><path d="M9 14l2 2l4 -4" /></svg>', 'active' => request()->segment(1) == 'projectx']
                    )->order(88);
                });
            }
        }
    }

    protected function isProjectxInstalled(): bool
    {
        return (new ModuleUtil())->isModuleInstalled('ProjectX');
    }
}
