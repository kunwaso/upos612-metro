<?php

namespace Modules\Projectauto\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Projectauto\Workflow\Support\WorkflowDefinitionResolver;

class WorkflowRegistryApiController extends Controller
{
    public function index(WorkflowDefinitionResolver $definitionResolver)
    {
        return response()->json([
            'success' => true,
            'data' => $definitionResolver->resolve(),
        ]);
    }
}
