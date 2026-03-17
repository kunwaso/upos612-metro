<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AddProjectxQuoteAdminOverridePermission extends Migration
{
    public function up()
    {
        Permission::firstOrCreate([
            'name' => 'projectx.quote.admin_override',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::where('name', 'projectx.quote.admin_override')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
