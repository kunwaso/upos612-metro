<?php

namespace Modules\StorageManager\Http\ViewComposers;

use Illuminate\View\View;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;

class StorageManagerToolbarViewComposer
{
    public function __construct(
        private StorageManagerToolbarNavUtil $toolbarNavUtil
    ) {
    }

    public function compose(View $view): void
    {
        // Toolbar nav is built in StorageToolbar::render() with map-location-id; this view has no
        // storageToolbarLocationId in data, so re-composing would drop implicit location context.
        if ($view->name() === 'storagemanager::components.storage-toolbar') {
            return;
        }

        $user = auth()->user();
        $routeName = request()->route()?->getName();

        $locationId = null;
        if ($view->offsetExists('storageToolbarLocationId')) {
            $fromView = $view->offsetGet('storageToolbarLocationId');
            if (is_int($fromView) && $fromView > 0) {
                $locationId = $fromView;
            }
        }
        if ($locationId === null) {
            $fromRequest = request()->integer('location_id');
            $locationId = $fromRequest > 0 ? $fromRequest : null;
        }

        $view->with('storageManagerToolbarMainNav', $this->toolbarNavUtil->buildMainNav(
            $user,
            is_string($routeName) ? $routeName : null,
            $locationId
        ));
    }
}
