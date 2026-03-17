<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddAichatChatPermissions extends Migration
{
    public function up()
    {
        $permissions = [
            'aichat.chat.view',
            'aichat.chat.edit',
            'aichat.chat.settings',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('user.view', 'aichat.chat.view');
        $this->copyRolePermissions('user.view', 'aichat.chat.edit');
        $this->copyRolePermissions('business_settings.access', 'aichat.chat.settings');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', [
            'aichat.chat.view',
            'aichat.chat.edit',
            'aichat.chat.settings',
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
