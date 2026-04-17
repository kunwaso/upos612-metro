<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'cms.blog.settings.view',
            'cms.blog.settings.update',
            'cms.blog.posts.view',
            'cms.blog.posts.create',
            'cms.blog.posts.update',
            'cms.blog.posts.delete',
            'cms.blog.posts.publish',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($roles as $role) {
            foreach ($permissions as $permissionName) {
                $role->givePermissionTo($permissionName);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'cms.blog.settings.view',
            'cms.blog.settings.update',
            'cms.blog.posts.view',
            'cms.blog.posts.create',
            'cms.blog.posts.update',
            'cms.blog.posts.delete',
            'cms.blog.posts.publish',
        ])->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
