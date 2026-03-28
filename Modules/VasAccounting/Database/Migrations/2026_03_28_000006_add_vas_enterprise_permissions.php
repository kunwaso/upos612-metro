<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddVasEnterprisePermissions extends Migration
{
    protected array $permissions = [
        'vas_accounting.cash_bank.manage',
        'vas_accounting.receivables.manage',
        'vas_accounting.payables.manage',
        'vas_accounting.invoices.manage',
        'vas_accounting.tools.manage',
        'vas_accounting.payroll.manage',
        'vas_accounting.contracts.manage',
        'vas_accounting.loans.manage',
        'vas_accounting.costing.manage',
        'vas_accounting.budgets.manage',
        'vas_accounting.integrations.manage',
    ];

    public function up()
    {
        foreach ($this->permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionMap = [
            'account.access' => [
                'vas_accounting.cash_bank.manage',
                'vas_accounting.receivables.manage',
                'vas_accounting.payables.manage',
                'vas_accounting.invoices.manage',
                'vas_accounting.tools.manage',
                'vas_accounting.contracts.manage',
                'vas_accounting.loans.manage',
                'vas_accounting.costing.manage',
                'vas_accounting.budgets.manage',
            ],
            'purchase.view' => [
                'vas_accounting.cash_bank.manage',
                'vas_accounting.payables.manage',
                'vas_accounting.tools.manage',
                'vas_accounting.costing.manage',
            ],
            'purchase_n_sell_report.view' => [
                'vas_accounting.receivables.manage',
                'vas_accounting.invoices.manage',
                'vas_accounting.budgets.manage',
                'vas_accounting.integrations.manage',
            ],
            'essentials.view_all_payroll' => [
                'vas_accounting.payroll.manage',
            ],
            'essentials.create_payroll' => [
                'vas_accounting.payroll.manage',
            ],
            'business_settings.access' => [
                'vas_accounting.integrations.manage',
            ],
        ];

        foreach ($permissionMap as $sourcePermission => $targets) {
            foreach ($targets as $targetPermission) {
                $this->copyRolePermissions($sourcePermission, $targetPermission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', $this->permissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function copyRolePermissions(string $sourcePermission, string $targetPermission): void
    {
        $roles = Role::whereHas('permissions', function ($query) use ($sourcePermission) {
            $query->where('name', $sourcePermission);
        })->get();

        $permission = Permission::where('name', $targetPermission)
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return;
        }

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }
    }
}
