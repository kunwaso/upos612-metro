<?php

namespace Modules\Projectauto\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;
use Modules\Projectauto\Entities\ProjectautoRule;
use Modules\Projectauto\Utils\ProjectautoUtil;

class DataController extends Controller
{
    public function user_permissions(): array
    {
        return [
            [
                'value' => 'projectauto.tasks.view',
                'label' => __('projectauto::lang.permission_tasks_view'),
                'default' => false,
            ],
            [
                'value' => 'projectauto.tasks.approve',
                'label' => __('projectauto::lang.permission_tasks_approve'),
                'default' => false,
            ],
            [
                'value' => 'projectauto.settings.manage',
                'label' => __('projectauto::lang.permission_settings_manage'),
                'default' => false,
            ],
        ];
    }

    public function modifyAdminMenu(): void
    {
        $moduleUtil = new ModuleUtil();
        if (! auth()->check() || ! $moduleUtil->isModuleInstalled('Projectauto')) {
            return;
        }

        if (! auth()->user()->can('projectauto.tasks.view') && ! auth()->user()->can('projectauto.settings.manage')) {
            return;
        }

        Menu::modify('admin-sidebar-menu', function ($menu) {
            $menu->dropdown(
                __('projectauto::lang.projectauto'),
                function ($sub) {
                    if (auth()->user()->can('projectauto.tasks.view')) {
                        $sub->url(
                            route('projectauto.tasks.index'),
                            __('projectauto::lang.pending_tasks'),
                            ['icon' => '', 'active' => request()->routeIs('projectauto.tasks.*')]
                        );
                    }

                    if (auth()->user()->can('projectauto.settings.manage')) {
                        $sub->url(
                            route('projectauto.settings.index'),
                            __('projectauto::lang.settings'),
                            ['icon' => '', 'active' => request()->routeIs('projectauto.settings.*')]
                        );
                    }
                },
                [
                    'icon' => '<i class="ki-duotone ki-gear fs-2"><span class="path1"></span><span class="path2"></span></i>',
                    'active' => request()->routeIs('projectauto.*'),
                ]
            )->order(87);
        });
    }

    public function after_payment_status_updated(array $data)
    {
        $transaction = $data['transaction'] ?? null;
        if (empty($transaction)) {
            return [];
        }

        try {
            return app(ProjectautoUtil::class)->createTasksFromTrigger(
                (int) $transaction->business_id,
                ProjectautoRule::TRIGGER_PAYMENT_STATUS_UPDATED,
                [
                    'transaction' => $transaction,
                    'old_status' => $data['old_status'] ?? null,
                ]
            );
        } catch (\Throwable $exception) {
            \Log::warning('Projectauto payment hook failed: '.$exception->getMessage());

            return [];
        }
    }

    public function after_sales_order_status_updated(array $data)
    {
        $transaction = $data['transaction'] ?? null;
        if (empty($transaction)) {
            return [];
        }

        try {
            return app(ProjectautoUtil::class)->createTasksFromTrigger(
                (int) $transaction->business_id,
                ProjectautoRule::TRIGGER_SALES_ORDER_STATUS_UPDATED,
                [
                    'transaction' => $transaction,
                    'old_status' => $data['old_status'] ?? null,
                ]
            );
        } catch (\Throwable $exception) {
            \Log::warning('Projectauto sales-order hook failed: '.$exception->getMessage());

            return [];
        }
    }
}
