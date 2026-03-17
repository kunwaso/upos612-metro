<?php

namespace App\Http\Controllers;

use App\Utils\ModuleUtil;

class WelcomeController extends Controller
{
    /** @var ModuleUtil */
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Show the welcome (landing) page. If any module provides a view via
     * getModuleData('welcome_view'), the first non-empty result is used;
     * otherwise the default view('welcome') is returned.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $moduleData = $this->moduleUtil->getModuleData('welcome_view');

        foreach ($moduleData as $result) {
            if (! empty($result['name'])) {
                return view($result['name'], $result['data'] ?? []);
            }
        }

        return view('welcome');
    }
}
