<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProjectxFabricPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $viewPermission = Permission::firstOrCreate([
            'name' => 'projectx.fabric.view',
            'guard_name' => 'web',
        ]);

        $createPermission = Permission::firstOrCreate([
            'name' => 'projectx.fabric.create',
            'guard_name' => 'web',
        ]);

        $this->copyRolePermissions('product.view', $viewPermission->name);
        $this->copyRolePermissions('product.create', $createPermission->name);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::whereIn('name', [
            'projectx.fabric.view',
            'projectx.fabric.create',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Copy existing role permission assignments to keep backward compatibility.
     *
     * @param  string  $sourcePermission
     * @param  string  $targetPermission
     * @return void
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
