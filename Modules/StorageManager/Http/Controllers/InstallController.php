<?php

namespace Modules\StorageManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\System;
use Composer\Semver\Comparator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class InstallController extends Controller
{
    protected string $moduleKey;

    protected string $moduleName;

    protected string $appVersion;

    public function __construct()
    {
        $this->moduleKey = 'storagemanager';
        $this->moduleName = 'StorageManager';
        $this->appVersion = (string) config('storagemanager.module_version', '1.0.0');
    }

    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $this->installSettings();

            $installedVersion = System::getProperty($this->moduleKey.'_version');
            if (empty($installedVersion)) {
                DB::statement('SET default_storage_engine=INNODB;');
                Artisan::call('module:migrate', ['module' => $this->moduleName, '--force' => true]);
                Artisan::call('module:publish', ['module' => $this->moduleName]);
                System::addProperty($this->moduleKey.'_version', $this->appVersion);
            }

            $this->bootstrapPermissions();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();
            \Log::error('StorageManager install failed: '.$exception->getMessage());

            $output = [
                'success' => false,
                'msg' => $exception->getMessage(),
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\Install\ModulesController::class, 'index'])
            ->with('status', $output);
    }

    public function update()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $installedVersion = System::getProperty($this->moduleKey.'_version');
        if (empty($installedVersion) || ! Comparator::greaterThan($this->appVersion, $installedVersion)) {
            abort(404);
        }

        try {
            DB::beginTransaction();

            $this->installSettings();

            DB::statement('SET default_storage_engine=INNODB;');
            Artisan::call('module:migrate', ['module' => $this->moduleName, '--force' => true]);
            Artisan::call('module:publish', ['module' => $this->moduleName]);
            System::setProperty($this->moduleKey.'_version', $this->appVersion);

            $this->bootstrapPermissions();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $exception) {
            DB::rollBack();
            \Log::error('StorageManager update failed: '.$exception->getMessage());

            $output = [
                'success' => false,
                'msg' => $exception->getMessage(),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    public function uninstall()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->moduleKey.'_version');
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $exception) {
            $output = [
                'success' => false,
                'msg' => $exception->getMessage(),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    protected function installSettings(): void
    {
        config(['app.debug' => true]);
        Artisan::call('config:clear');
    }

    protected function bootstrapPermissions(): void
    {
        $permissions = [
            'storage_manager.view',
            'storage_manager.manage',
            'storage_manager.operate',
            'storage_manager.approve',
            'storage_manager.count',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRoles = Role::where('name', 'like', 'Admin#%')->get();
        foreach ($adminRoles as $role) {
            foreach ($permissions as $permissionName) {
                $role->givePermissionTo($permissionName);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
