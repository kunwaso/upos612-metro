<?php

namespace Modules\Projectauto\Http\Controllers;

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

    protected string $appVersion;

    public function __construct()
    {
        $this->moduleKey = 'projectauto';
        $this->appVersion = (string) config('projectauto.module_version');
    }

    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $this->installSettings();
            DB::statement('SET default_storage_engine=INNODB;');

            Artisan::call('module:migrate', ['module' => 'Projectauto', '--force' => true]);
            Artisan::call('module:publish', ['module' => 'Projectauto']);

            System::addProperty($this->moduleKey.'_version', $this->appVersion);
            $this->bootstrapPermissions();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.install_success'),
            ];
        } catch (\Exception $exception) {
            DB::rollBack();
            \Log::emergency('File:'.$exception->getFile().'Line:'.$exception->getLine().'Message:'.$exception->getMessage());

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

            Artisan::call('module:migrate', ['module' => 'Projectauto', '--force' => true]);
            Artisan::call('module:publish', ['module' => 'Projectauto']);

            System::setProperty($this->moduleKey.'_version', $this->appVersion);
            $this->bootstrapPermissions();

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.update_success', ['version' => $this->appVersion]),
            ];
        } catch (\Exception $exception) {
            DB::rollBack();
            \Log::emergency('File:'.$exception->getFile().'Line:'.$exception->getLine().'Message:'.$exception->getMessage());

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

            $output = [
                'success' => true,
                'msg' => __('projectauto::lang.uninstall_success'),
            ];
        } catch (\Exception $exception) {
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
            'projectauto.tasks.view',
            'projectauto.tasks.approve',
            'projectauto.settings.manage',
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
