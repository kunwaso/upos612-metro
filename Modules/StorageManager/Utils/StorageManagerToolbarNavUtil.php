<?php

namespace Modules\StorageManager\Utils;

use App\User;

class StorageManagerToolbarNavUtil
{
    /**
     * First breadcrumb links to the warehouse map; optionally preserves location context.
     *
     * @param  array<int, array{label: string, url?: string|null}>  $segmentsAfterRoot
     * @return array<int, array{label: string, url?: string|null}>
     */
    public static function breadcrumbsAfterRoot(array $segmentsAfterRoot, ?int $mapLocationId = null): array
    {
        $rootUrl = ($mapLocationId !== null && $mapLocationId > 0)
            ? route('storage-manager.index', ['location_id' => $mapLocationId])
            : route('storage-manager.index');

        return array_merge(
            [['label' => __('lang_v1.storage_manager'), 'url' => $rootUrl]],
            $segmentsAfterRoot
        );
    }

    /**
     * Build main toolbar navigation links for the Storage Manager module.
     *
     * Permission gates mirror the warehouse map toolbar in storagemanager::index:
     * - storage_manager.manage: add slot, areas, settings
     * - canany(storage_manager.view, storage_manager.operate): inbound, putaway, outbound
     *
     * When {@code $locationId} is a positive integer, it is passed to the warehouse map URL
     * so the user returns to the same location context.
     *
     * @return array<int, array{key: string, href: string, label: string, isActive: bool, cssClass: string, icon_html?: string}>
     */
    public function buildMainNav(?User $user, ?string $currentRouteName, ?int $locationId = null): array
    {
        if ($user === null) {
            return [];
        }

        $current = $currentRouteName ?? '';
        $mapParams = ($locationId !== null && $locationId > 0) ? ['location_id' => $locationId] : [];

        $items = [];

        $mapActive = $this->isWarehouseMapActive($current);
        $items[] = [
            'key' => 'warehouse_map',
            'href' => route('storage-manager.index', $mapParams),
            'label' => __('lang_v1.warehouse_map'),
            'isActive' => $mapActive,
            'cssClass' => $this->toggleButtonClass($mapActive),
        ];

        if ($user->can('storage_manager.manage')) {
            $createActive = $current === 'storage-manager.slots.create';
            $items[] = [
                'key' => 'add_storage_slot',
                'href' => route('storage-manager.slots.create'),
                'label' => __('lang_v1.add_storage_slot'),
                'isActive' => $createActive,
                'cssClass' => 'btn btn-sm btn-primary',
                'icon_html' => '<i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>',
            ];
        }

        $slotsSectionActive = $this->isStorageSlotsSectionActive($current);
        $items[] = [
            'key' => 'storage_slots',
            'href' => route('storage-manager.slots.index'),
            'label' => __('lang_v1.storage_slots'),
            'isActive' => $slotsSectionActive,
            'cssClass' => $this->toggleButtonClass($slotsSectionActive),
            'icon_html' => '<i class="ki-duotone ki-element-11 fs-4 me-1"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>',
        ];

        if ($user->can('storage_manager.manage')) {
            $areasActive = str_starts_with($current, 'storage-manager.areas.');
            $items[] = [
                'key' => 'warehouse_areas',
                'href' => route('storage-manager.areas.index'),
                'label' => __('lang_v1.warehouse_areas'),
                'isActive' => $areasActive,
                'cssClass' => $this->toggleButtonClass($areasActive),
            ];

            $settingsActive = str_starts_with($current, 'storage-manager.settings.');
            $items[] = [
                'key' => 'warehouse_settings',
                'href' => route('storage-manager.settings.index'),
                'label' => __('lang_v1.warehouse_settings'),
                'isActive' => $settingsActive,
                'cssClass' => $this->toggleButtonClass($settingsActive),
            ];
        }

        if ($user->canAny(['storage_manager.view', 'storage_manager.operate'])) {
            $inboundActive = str_starts_with($current, 'storage-manager.inbound.');
            $items[] = [
                'key' => 'expected_receipts',
                'href' => route('storage-manager.inbound.index'),
                'label' => __('lang_v1.expected_receipts'),
                'isActive' => $inboundActive,
                'cssClass' => $this->toggleButtonClass($inboundActive),
            ];

            $putawayActive = str_starts_with($current, 'storage-manager.putaway.');
            $items[] = [
                'key' => 'putaway_queue',
                'href' => route('storage-manager.putaway.index'),
                'label' => __('lang_v1.putaway_queue'),
                'isActive' => $putawayActive,
                'cssClass' => $this->toggleButtonClass($putawayActive),
            ];

            $outboundActive = str_starts_with($current, 'storage-manager.outbound.');
            $items[] = [
                'key' => 'outbound_execution',
                'href' => route('storage-manager.outbound.index'),
                'label' => __('lang_v1.outbound_execution'),
                'isActive' => $outboundActive,
                'cssClass' => $this->toggleButtonClass($outboundActive),
            ];
        }

        $towerActive = $current === 'storage-manager.control-tower.index';
        $items[] = [
            'key' => 'control_tower',
            'href' => route('storage-manager.control-tower.index'),
            'label' => __('lang_v1.control_tower'),
            'isActive' => $towerActive,
            'cssClass' => $this->toggleButtonClass($towerActive),
        ];

        return $items;
    }

    private function toggleButtonClass(bool $isActive): string
    {
        return $isActive ? 'btn btn-sm btn-light-primary' : 'btn btn-sm btn-light';
    }

    private function isWarehouseMapActive(string $current): bool
    {
        return $current === 'storage-manager.index' || $current === 'storage-manager.running-out';
    }

    private function isStorageSlotsSectionActive(string $current): bool
    {
        if (! str_starts_with($current, 'storage-manager.slots.')) {
            return false;
        }

        return $current !== 'storage-manager.slots.create';
    }
}
