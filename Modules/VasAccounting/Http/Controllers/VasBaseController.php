<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class VasBaseController extends Controller
{
    protected function authorizePermission(string $permission): void
    {
        if (! auth()->user()->can($permission)) {
            abort(403, __('vasaccounting::lang.unauthorized_action'));
        }
    }

    protected function businessId(Request $request): int
    {
        return (int) $request->session()->get('user.business_id');
    }

    protected function selectedLocationId(Request $request): ?int
    {
        $locationId = (int) $request->query('location_id', 0);

        return $locationId > 0 ? $locationId : null;
    }
}
