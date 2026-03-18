<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddAichatQuoteWizardPermission extends Migration
{
    public function up()
    {
        Permission::firstOrCreate([
            'name' => 'aichat.quote_wizard.use',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyRolePermissions('product_quote.create', 'aichat.quote_wizard.use');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::where('name', 'aichat.quote_wizard.use')
            ->where('guard_name', 'web')
            ->delete();

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
