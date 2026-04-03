<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddVasInventoryDestroyDraftPermission extends Migration
{
    protected string $permissionName = 'vas_accounting.inventory.destroy_draft';

    public function up()
    {
        $permission = Permission::firstOrCreate([
            'name' => $this->permissionName,
            'guard_name' => 'web',
        ]);

        // Draft warehouse document deletion is reserved for admin roles only.
        Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', 'Admin#%')
            ->get()
            ->each(function (Role $role) use ($permission) {
                $role->givePermissionTo($permission);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::query()
            ->where('name', $this->permissionName)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
