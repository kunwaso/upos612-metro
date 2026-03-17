<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProjectxQuotePermissions extends Migration
{
    public function up()
    {
        $permissions = [
            'projectx.quote.view',
            'projectx.quote.create',
            'projectx.quote.send',
            'projectx.quote.release_invoice',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('sell.view', 'projectx.quote.view');
        $this->copyRolePermissions('projectx.fabric.view', 'projectx.quote.view');
        $this->copyRolePermissions('sell.create', 'projectx.quote.create');
        $this->copyRolePermissions('projectx.fabric.create', 'projectx.quote.create');
        $this->copyRolePermissions('sell.create', 'projectx.quote.send');
        $this->copyRolePermissions('projectx.fabric.create', 'projectx.quote.send');
        $this->copyRolePermissions('sell.create', 'projectx.quote.release_invoice');
        $this->copyRolePermissions('projectx.fabric.create', 'projectx.quote.release_invoice');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'projectx.quote.view',
            'projectx.quote.create',
            'projectx.quote.send',
            'projectx.quote.release_invoice',
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
