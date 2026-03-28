<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddVasAccountingPermissions extends Migration
{
    protected array $permissions = [
        'vas_accounting.access',
        'vas_accounting.setup.manage',
        'vas_accounting.chart.manage',
        'vas_accounting.vouchers.manage',
        'vas_accounting.posting.replay',
        'vas_accounting.periods.manage',
        'vas_accounting.inventory.manage',
        'vas_accounting.assets.manage',
        'vas_accounting.tax.manage',
        'vas_accounting.einvoice.manage',
        'vas_accounting.reports.view',
        'vas_accounting.close.manage',
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

        $this->copyRolePermissions('account.access', 'vas_accounting.access');
        $this->copyRolePermissions('business_settings.access', 'vas_accounting.setup.manage');
        $this->copyRolePermissions('account.access', 'vas_accounting.chart.manage');
        $this->copyRolePermissions('account.access', 'vas_accounting.vouchers.manage');
        $this->copyRolePermissions('account.access', 'vas_accounting.periods.manage');
        $this->copyRolePermissions('purchase_n_sell_report.view', 'vas_accounting.reports.view');
        $this->copyRolePermissions('purchase.view', 'vas_accounting.inventory.manage');
        $this->copyRolePermissions('purchase.view', 'vas_accounting.assets.manage');
        $this->copyRolePermissions('purchase_n_sell_report.view', 'vas_accounting.tax.manage');
        $this->copyRolePermissions('purchase_n_sell_report.view', 'vas_accounting.einvoice.manage');
        $this->copyRolePermissions('business_settings.access', 'vas_accounting.close.manage');
        $this->copyRolePermissions('account.access', 'vas_accounting.posting.replay');

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
