<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProjectxTrimPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            'projectx.trim.view',
            'projectx.trim.create',
            'projectx.trim.edit',
            'projectx.trim.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('projectx.fabric.view', 'projectx.trim.view');
        $this->copyRolePermissions('product.view', 'projectx.trim.view');

        $this->copyRolePermissions('projectx.fabric.create', 'projectx.trim.create');
        $this->copyRolePermissions('product.create', 'projectx.trim.create');

        $this->copyRolePermissions('projectx.fabric.create', 'projectx.trim.edit');
        $this->copyRolePermissions('product.create', 'projectx.trim.edit');

        $this->copyRolePermissions('projectx.fabric.create', 'projectx.trim.delete');
        $this->copyRolePermissions('product.create', 'projectx.trim.delete');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'projectx.trim.view',
            'projectx.trim.create',
            'projectx.trim.edit',
            'projectx.trim.delete',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function copyRolePermissions(string $sourcePermission, string $targetPermission): void
    {
        $permission = Permission::where('name', $targetPermission)
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return;
        }

        $roles = Role::whereHas('permissions', function ($query) use ($sourcePermission) {
            $query->where('name', $sourcePermission);
        })->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }
    }
}
