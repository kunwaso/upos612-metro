<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProjectxFabricActivityDeletePermission extends Migration
{
    public function up()
    {
        $deletePermission = Permission::firstOrCreate([
            'name' => 'projectx.fabric.activity.delete',
            'guard_name' => 'web',
        ]);

        $this->copyRolePermissions('projectx.fabric.create', $deletePermission->name);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::where('name', 'projectx.fabric.activity.delete')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Copy existing role permission assignments from source permission.
     */
    protected function copyRolePermissions($sourcePermission, $targetPermission)
    {
        $roles = Role::whereHas('permissions', function ($query) use ($sourcePermission) {
            $query->where('name', $sourcePermission);
        })->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($targetPermission);
        }
    }
}
