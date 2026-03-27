<?php

namespace Modules\Core\app\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'module' => 'core',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
