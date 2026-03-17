<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProjectxQuoteSalesOrderPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            'projectx.quote.edit',
            'projectx.quote.delete',
            'projectx.sales_order.edit',
            'projectx.sales_order.update_status',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('projectx.quote.create', 'projectx.quote.edit');
        $this->copyRolePermissions('projectx.quote.create', 'projectx.quote.delete');
        $this->copyRolePermissions('direct_sell.update', 'projectx.sales_order.edit');
        $this->copyRolePermissions('sell.update', 'projectx.sales_order.edit');
        $this->copyRolePermissions('direct_sell.update', 'projectx.sales_order.update_status');
        $this->copyRolePermissions('sell.update', 'projectx.sales_order.update_status');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'projectx.quote.edit',
            'projectx.quote.delete',
            'projectx.sales_order.edit',
            'projectx.sales_order.update_status',
        ])->delete();

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

