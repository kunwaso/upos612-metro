<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProductQuotePermissions extends Migration
{
    public function up()
    {
        $permissions = [
            'product_quote.view',
            'product_quote.create',
            'product_quote.send',
            'product_quote.release_invoice',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('sell.view', 'product_quote.view');
        $this->copyRolePermissions('product.view', 'product_quote.view');
        $this->copyRolePermissions('sell.create', 'product_quote.create');
        $this->copyRolePermissions('direct_sell.access', 'product_quote.create');
        $this->copyRolePermissions('sell.create', 'product_quote.send');
        $this->copyRolePermissions('direct_sell.access', 'product_quote.send');
        $this->copyRolePermissions('sell.create', 'product_quote.release_invoice');
        $this->copyRolePermissions('direct_sell.access', 'product_quote.release_invoice');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'product_quote.view',
            'product_quote.create',
            'product_quote.send',
            'product_quote.release_invoice',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function copyRolePermissions($sourcePermission, $targetPermission)
    {
        $roles = Role::whereHas('permissions', function ($query) use ($sourcePermission) {
            $query->where('name', $sourcePermission);
        })->get();

        $permission = Permission::where('name', $targetPermission)->where('guard_name', 'web')->first();
        if (! $permission) {
            return;
        }

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }
    }
}
