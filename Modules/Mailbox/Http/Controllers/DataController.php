<?php

namespace Modules\Mailbox\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    public function user_permissions(): array
    {
        return [
            [
                'value' => 'mailbox.view',
                'label' => __('mailbox::lang.permission_view'),
                'default' => false,
            ],
            [
                'value' => 'mailbox.manage_accounts',
                'label' => __('mailbox::lang.permission_manage_accounts'),
                'default' => false,
            ],
            [
                'value' => 'mailbox.send',
                'label' => __('mailbox::lang.permission_send'),
                'default' => false,
            ],
        ];
    }

    public function modifyAdminMenu(): void
    {
        $moduleUtil = new ModuleUtil();
        if (! auth()->check() || ! $moduleUtil->isModuleInstalled('Mailbox')) {
            return;
        }

        if (
            ! auth()->user()->can('mailbox.view')
            && ! auth()->user()->can('mailbox.manage_accounts')
            && ! auth()->user()->can('mailbox.send')
        ) {
            return;
        }

        Menu::modify('admin-sidebar-menu', function ($menu) {
            $menu->dropdown(
                __('mailbox::lang.mailbox'),
                function ($sub) {
                    if (auth()->user()->can('mailbox.view')) {
                        $sub->url(
                            route('mailbox.index'),
                            __('mailbox::lang.inbox'),
                            ['icon' => '', 'active' => request()->routeIs('mailbox.index') || request()->routeIs('mailbox.threads.*')]
                        );
                    }

                    if (auth()->user()->can('mailbox.manage_accounts')) {
                        $sub->url(
                            route('mailbox.accounts.index'),
                            __('mailbox::lang.accounts'),
                            ['icon' => '', 'active' => request()->routeIs('mailbox.accounts.*')]
                        );
                    }

                    if (auth()->user()->can('mailbox.send')) {
                        $sub->url(
                            route('mailbox.compose.create'),
                            __('mailbox::lang.compose'),
                            ['icon' => '', 'active' => request()->routeIs('mailbox.compose.*')]
                        );
                    }
                },
                [
                    'icon' => '<i class="ki-duotone ki-message-text-2 fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>',
                    'active' => request()->routeIs('mailbox.*'),
                ]
            )->order(90);
        });
    }
}
