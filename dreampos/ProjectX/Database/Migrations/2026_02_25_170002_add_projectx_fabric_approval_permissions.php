<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProjectxFabricApprovalPermissions extends Migration
{
    public function up()
    {
        $submitPermission = Permission::firstOrCreate([
            'name' => 'projectx.fabric.submit',
            'guard_name' => 'web',
        ]);

        $approvePermission = Permission::firstOrCreate([
            'name' => 'projectx.fabric.approve',
            'guard_name' => 'web',
        ]);

        $rejectPermission = Permission::firstOrCreate([
            'name' => 'projectx.fabric.reject',
            'guard_name' => 'web',
        ]);

        $this->copyRolePermissions('projectx.fabric.create', $submitPermission->name);
        $this->copyRolePermissions('projectx.fabric.create', $approvePermission->name);
        $this->copyRolePermissions('projectx.fabric.create', $rejectPermission->name);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'projectx.fabric.submit',
            'projectx.fabric.approve',
            'projectx.fabric.reject',
        ])->delete();

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