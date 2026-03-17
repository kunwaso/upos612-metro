<?php

namespace Modules\Aichat\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    public function user_permissions(): array
    {
        return [
            [
                'value' => 'aichat.chat.view',
                'label' => __('aichat::lang.permission_chat_view'),
                'default' => false,
            ],
            [
                'value' => 'aichat.chat.edit',
                'label' => __('aichat::lang.permission_chat_edit'),
                'default' => false,
            ],
            [
                'value' => 'aichat.chat.settings',
                'label' => __('aichat::lang.permission_chat_settings'),
                'default' => false,
            ],
        ];
    }

    public function modifyAdminMenu(): void
    {
        $module_util = new ModuleUtil();
        if (! $module_util->isModuleInstalled('Aichat') || ! auth()->check()) {
            return;
        }

        if (
            ! auth()->user()->can('aichat.chat.view')
            && ! auth()->user()->can('aichat.chat.settings')
            && ! auth()->user()->can('aichat.manage_all_memories')
        ) {
            return;
        }

        Menu::modify('admin-sidebar-menu', function ($menu) {
            $menu->dropdown(
                __('aichat::lang.ai_chat'),
                function ($sub) {
                    if (auth()->user()->can('aichat.chat.view')) {
                        $sub->url(
                            route('aichat.chat.index'),
                            __('aichat::lang.ai_chat'),
                            ['icon' => '', 'active' => request()->routeIs('aichat.chat.index')]
                        );
                    }

                    if (auth()->user()->can('aichat.chat.settings')) {
                        $sub->url(
                            route('aichat.chat.settings'),
                            __('aichat::lang.ai_chat_settings'),
                            ['icon' => '', 'active' => request()->routeIs('aichat.chat.settings*')]
                        );
                    }

                    if (auth()->user()->can('aichat.manage_all_memories')) {
                        $sub->url(
                            route('aichat.chat.settings.memories.admin'),
                            __('aichat::lang.chat_memory_admin_menu'),
                            ['icon' => '', 'active' => request()->routeIs('aichat.chat.settings.memories.admin*')]
                        );
                    }
                },
                [
                    'icon' => '<i class="ki-duotone ki-message-text-2 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>',
                    'active' => request()->routeIs('aichat.chat.*'),
                ]
            )->order(88);
        });
    }
}
