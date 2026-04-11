<?php

namespace Modules\StorageManager\View\Components;

use App\User;
use Illuminate\View\Component;
use Modules\StorageManager\Utils\StorageManagerToolbarNavUtil;

class StorageToolbar extends Component
{
    /**
     * @param  array<int, array{label: string, url?: string|null}>  $breadcrumbs
     */
    public function __construct(
        public string $title,
        public array $breadcrumbs,
        public bool $showMainNav = true,
        public string $toolbarWrapperClass = '',
        public ?int $mapLocationId = null,
    ) {
    }

    public function render()
    {
        $toolbarNavUtil = app(StorageManagerToolbarNavUtil::class);

        $user = auth()->user();
        $routeName = request()->route()?->getName();

        $locationId = ($this->mapLocationId !== null && $this->mapLocationId > 0)
            ? $this->mapLocationId
            : null;
        if ($locationId === null) {
            $fromRequest = request()->integer('location_id');
            $locationId = $fromRequest > 0 ? $fromRequest : null;
        }

        return view('storagemanager::components.storage-toolbar', [
            'storageManagerToolbarMainNav' => $toolbarNavUtil->buildMainNav(
                $user instanceof User ? $user : null,
                is_string($routeName) ? $routeName : null,
                $locationId
            ),
        ]);
    }
}
