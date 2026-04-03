<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    public function user_permissions(): array
    {
        return [
            [
                'value' => 'storage_manager.view',
                'label' => __('lang_v1.permission_storage_manager_view'),
                'default' => false,
            ],
            [
                'value' => 'storage_manager.manage',
                'label' => __('lang_v1.permission_storage_manager_manage'),
                'default' => false,
            ],
            [
                'value' => 'storage_manager.operate',
                'label' => __('lang_v1.permission_storage_manager_operate'),
                'default' => false,
            ],
            [
                'value' => 'storage_manager.approve',
                'label' => __('lang_v1.permission_storage_manager_approve'),
                'default' => false,
            ],
            [
                'value' => 'storage_manager.count',
                'label' => __('lang_v1.permission_storage_manager_count'),
                'default' => false,
            ],
        ];
    }

    public function modifyAdminMenu(): void
    {
        $moduleUtil = new ModuleUtil();
        if (! auth()->check() || ! $moduleUtil->isModuleInstalled('StorageManager')) {
            return;
        }

        if (! auth()->user()->can('storage_manager.view') && ! auth()->user()->can('storage_manager.manage')) {
            return;
        }

        Menu::modify('admin-sidebar-menu', function ($menu) {
            $menu->dropdown(
                __('lang_v1.storage_manager'),
                function ($sub) {
                    if (auth()->user()->can('storage_manager.view')) {
                        $sub->url(
                            route('storage-manager.index'),
                            __('lang_v1.warehouse_map'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.index')]
                        );
                    }

                    if (auth()->user()->can('storage_manager.manage')) {
                        $sub->url(
                            route('storage-manager.slots.index'),
                            __('lang_v1.storage_slots'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.slots.*')]
                        );

                        $sub->url(
                            route('storage-manager.areas.index'),
                            __('lang_v1.warehouse_areas'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.areas.*')]
                        );

                        $sub->url(
                            route('storage-manager.settings.index'),
                            __('lang_v1.warehouse_settings'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.settings.*')]
                        );
                    }

                    if (auth()->user()->can('storage_manager.view') || auth()->user()->can('storage_manager.operate')) {
                        $sub->url(
                            route('storage-manager.inbound.index'),
                            __('lang_v1.expected_receipts'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.inbound.*')]
                        );

                        $sub->url(
                            route('storage-manager.putaway.index'),
                            __('lang_v1.putaway_queue'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.putaway.*')]
                        );

                        $sub->url(
                            route('storage-manager.transfers.index'),
                            __('lang_v1.transfer_execution'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.transfers.*')]
                        );

                        $sub->url(
                            route('storage-manager.replenishment.index'),
                            __('lang_v1.replenishment_queue'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.replenishment.*')]
                        );

                        $sub->url(
                            route('storage-manager.outbound.index'),
                            __('lang_v1.outbound_execution'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.outbound.*')]
                        );

                        $sub->url(
                            route('storage-manager.damage.index'),
                            __('lang_v1.damage_quarantine'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.damage.*')]
                        );
                    }

                    if (auth()->user()->can('storage_manager.view') || auth()->user()->can('storage_manager.count')) {
                        $sub->url(
                            route('storage-manager.counts.index'),
                            __('lang_v1.cycle_count_sessions'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.counts.*')]
                        );
                    }

                    if (auth()->user()->can('storage_manager.view') || auth()->user()->can('storage_manager.approve')) {
                        $sub->url(
                            route('storage-manager.control-tower.index'),
                            __('lang_v1.control_tower'),
                            ['icon' => '', 'active' => request()->routeIs('storage-manager.control-tower.*')]
                        );
                    }
                },
                [
                    'icon' => '<i class="ki-duotone ki-package fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>',
                    'active' => request()->routeIs('storage-manager.*'),
                ]
            )->order(89);
        });
    }
}
