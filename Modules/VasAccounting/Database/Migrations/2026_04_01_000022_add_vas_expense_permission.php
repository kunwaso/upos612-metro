<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    protected string $permission = 'vas_accounting.expenses.manage';

    public function up(): void
    {
        Permission::firstOrCreate([
            'name' => $this->permission,
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['account.access', 'purchase.view'] as $sourcePermission) {
            $this->copyRolePermissions($sourcePermission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', $this->permission)
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function copyRolePermissions(string $sourcePermission): void
    {
        $permission = Permission::query()
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->first();

        if (! $permission) {
            return;
        }

        $roles = Role::query()
            ->whereHas('permissions', function ($query) use ($sourcePermission) {
                $query->where('name', $sourcePermission);
            })
            ->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }
    }
};
