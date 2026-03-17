<?php

namespace Modules\ProjectX\Http\Controllers;

use App\System;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallController extends Controller
{
    public function __construct()
    {
        $this->module_name = 'projectx';
        $this->appVersion = config('projectx.module_version');
        $this->module_display_name = 'ProjectX';
    }

    public function index()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '512M');

        $is_installed = System::getProperty($this->module_name . '_version');
        if (! empty($is_installed)) {
            abort(404);
        }

        try {
            DB::beginTransaction();

            DB::statement('SET default_storage_engine=INNODB;');
            Artisan::call('module:migrate', ['module' => 'ProjectX', '--force' => true]);
            System::addProperty($this->module_name . '_version', $this->appVersion);

            DB::commit();

            $this->publishAssets();

            $output = [
                'success' => true,
                'msg' => 'ProjectX module installed successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\HomeController::class, 'index'])
            ->with('status', $output);
    }

    public function install()
    {
        return $this->index();
    }

    public function uninstall()
    {
        if (! auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            System::removeProperty($this->module_name . '_version');

            $this->removeAssets();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            $output = [
                'success' => false,
                'msg' => $e->getMessage(),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    protected function publishAssets()
    {
        $src = module_path('ProjectX', 'Resources/assets');
        $dest = public_path('modules/projectx');

        if (is_dir($src)) {
            File::copyDirectory($src, $dest);
        }
    }

    protected function removeAssets()
    {
        $dest = public_path('modules/projectx');

        if (File::isDirectory($dest)) {
            File::deleteDirectory($dest);
        }
    }
}
