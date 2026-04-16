<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddVasComplianceAndFilingPermissions extends Migration
{
    protected array $permissions = [
        'vas_accounting.compliance.admin',
        'vas_accounting.filing.operator',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('vas_accounting.setup.manage', 'vas_accounting.compliance.admin');
        $this->copyRolePermissions('vas_accounting.tax.manage', 'vas_accounting.filing.operator');
        $this->copyRolePermissions('vas_accounting.einvoice.manage', 'vas_accounting.filing.operator');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->delete();
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
