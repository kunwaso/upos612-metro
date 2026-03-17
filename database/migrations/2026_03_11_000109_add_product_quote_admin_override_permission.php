<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AddProductQuoteAdminOverridePermission extends Migration
{
    public function up()
    {
        Permission::firstOrCreate([
            'name' => 'product_quote.admin_override',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::where('name', 'product_quote.admin_override')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
