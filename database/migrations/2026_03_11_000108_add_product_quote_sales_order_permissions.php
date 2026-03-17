<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProductQuoteSalesOrderPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            'product_quote.edit',
            'product_quote.delete',
            'product_sales_order.edit',
            'product_sales_order.update_status',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('product_quote.create', 'product_quote.edit');
        $this->copyRolePermissions('product_quote.create', 'product_quote.delete');
        $this->copyRolePermissions('direct_sell.update', 'product_sales_order.edit');
        $this->copyRolePermissions('sell.update', 'product_sales_order.edit');
        $this->copyRolePermissions('direct_sell.update', 'product_sales_order.update_status');
        $this->copyRolePermissions('sell.update', 'product_sales_order.update_status');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'product_quote.edit',
            'product_quote.delete',
            'product_sales_order.edit',
            'product_sales_order.update_status',
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

