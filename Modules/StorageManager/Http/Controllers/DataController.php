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
