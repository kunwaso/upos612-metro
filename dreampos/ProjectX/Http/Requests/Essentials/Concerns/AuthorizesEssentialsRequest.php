<?php

namespace Modules\ProjectX\Http\Requests\Essentials\Concerns;

use App\Utils\ModuleUtil;

trait AuthorizesEssentialsRequest
{
    protected function businessId(): int
    {
        return (int) $this->session()->get('user.business_id');
    }

    protected function hasEssentialsAccess(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $business_id = $this->businessId();
        $module_util = app(ModuleUtil::class);

        return auth()->user()->can('superadmin')
            || (bool) $module_util->hasThePermissionInSubscription($business_id, 'essentials_module');
    }

    protected function isBusinessAdmin(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $module_util = app(ModuleUtil::class);

        return (bool) $module_util->is_admin(auth()->user(), $this->businessId());
    }

    protected function hasEssentialsAccessOrAdmin(): bool
    {
        return $this->hasEssentialsAccess() || $this->isBusinessAdmin();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function hasAnyPermission(array $permissions): bool
    {
        if (auth()->user()->can('superadmin')) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (auth()->user()->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
