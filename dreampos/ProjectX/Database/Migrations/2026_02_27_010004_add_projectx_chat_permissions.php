<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddProjectxChatPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            'projectx.chat.view',
            'projectx.chat.edit',
            'projectx.chat.settings',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('projectx.quote.view', 'projectx.chat.view');
        $this->copyRolePermissions('projectx.quote.create', 'projectx.chat.edit');
        $this->copyRolePermissions('projectx.quote.edit', 'projectx.chat.settings');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'projectx.chat.view',
            'projectx.chat.edit',
            'projectx.chat.settings',
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

