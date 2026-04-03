<?php

namespace Modules\VasAccounting\Http\Controllers;

use App\Product;
use App\BusinessLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Modules\VasAccounting\Entities\VasAccount;
use Modules\VasAccounting\Entities\VasWarehouse;
use Modules\VasAccounting\Entities\VasInventoryDocument;
use Modules\VasAccounting\Http\Requests\DestroyInventoryDocumentRequest;
use Modules\VasAccounting\Http\Requests\StoreInventoryDocumentRequest;
use Modules\VasAccounting\Http\Requests\StoreWarehouseRequest;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Services\VasWarehouseDocumentService;
use Modules\VasAccounting\Utils\InventoryDocumentLifecycleUtil;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Throwable;

class InventoryController extends VasBaseController
{
    public function __construct(
        protected VasInventoryValuationService $inventoryValuationService,
        protected VasAccountingUtil $vasUtil,
        protected OperationsAssetReportUtil $operationsAssetReportUtil,
        protected VasWarehouseDocumentService $warehouseDocumentService,
        protected InventoryDocumentLifecycleUtil $inventoryDocumentLifecycleUtil
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePermission('vas_accounting.inventory.manage');

        $businessId = $this->businessId($request);
        $selectedLocationId = $this->selectedLocationId($request);
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->vasUtil->defaultFeatureFlags(), (array) $settings->feature_flags);

        if (($featureFlags['inventory'] ?? true) === false) {
            abort(404);
        }

        $rows = $this->inventoryValuationService->summaries($businessId);
        if ($selectedLocationId) {
            $rows = $rows->filter(fn (array $row) => (int) ($row['location_id'] ?? 0) === $selectedLocationId)->values();
        }

        $totals = [
            'sku_count' => $rows->count(),
            'quantity_on_hand' => (float) $rows->sum('qty_available'),
            'inventory_value' => (float) $rows->sum('inventory_value'),
        ];
        $warehouses = Schema::hasTable('vas_warehouses')
            ? VasWarehouse::query()
                ->with('businessLocation')
                ->where('business_id', $businessId)
                ->when($selectedLocationId, fn ($query) => $query->where('business_location_id', $selectedLocationId))
                ->orderBy('code')
                ->get()
            : collect();
        $warehouseSummary = $this->operationsAssetReportUtil->warehouseSummary($businessId);
        $movementRows = $this->operationsAssetReportUtil->inventoryMovementRows($businessId);
        $reconciliationRows = $this->operationsAssetReportUtil->warehouseReconciliationRows($businessId);
        $inventoryDocuments = $this->warehouseDocumentService->recentDocuments($businessId);

        if ($selectedLocationId) {
            $movementRows = $movementRows->filter(fn ($row) => (int) ($row->location_id ?? 0) === $selectedLocationId)->values();
            $reconciliationRows = $reconciliationRows
                ->filter(fn (array $row) => (int) ($row['location_id'] ?? 0) === $selectedLocationId)
                ->values();
            $inventoryDocuments = $inventoryDocuments
                ->filter(fn ($document) => (int) ($document->business_location_id ?? 0) === $selectedLocationId)
                ->values();
            $warehouseSummary = [
                'warehouse_count' => $warehouses->count(),
                'active_warehouses' => $warehouses->where('status', 'active')->count(),
                'stock_locations' => $rows->pluck('location_id')->filter()->unique()->count(),
                'uncovered_locations' => 0,
                'unposted_documents' => $inventoryDocuments->whereIn('status', ['draft', 'pending_approval', 'approved'])->count(),
                'warehouse_discrepancies' => $reconciliationRows->where('coverage_status', '!=', 'aligned')->count(),
                'storage_sync_pending' => 0,
                'storage_sync_errors' => 0,
                'storage_reconcile_errors' => 0,
            ];
        }

        return view('vasaccounting::inventory.index', [
            'rows' => $rows,
            'totals' => $totals,
            'warehouses' => $warehouses,
            'warehouseSummary' => $warehouseSummary,
            'movementRows' => $movementRows,
            'reconciliationRows' => $reconciliationRows,
            'locationOptions' => BusinessLocation::forDropdown($businessId),
            'selectedLocationId' => $selectedLocationId,
            'productOptions' => Product::query()
                ->where('business_id', $businessId)
                ->where('is_inactive', 0)
                ->orderBy('name')
                ->limit(300)
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($product) => [(int) $product->id => $product->name]),
            'offsetAccountOptions' => VasAccount::query()
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->where('allows_manual_entries', true)
                ->orderBy('account_code')
                ->limit(300)
                ->get(['id', 'account_code', 'account_name'])
                ->mapWithKeys(fn ($account) => [(int) $account->id => $account->account_code . ' - ' . $account->account_name]),
            'inventoryDocuments' => $inventoryDocuments,
        ]);
    }

    public function storeWarehouse(StoreWarehouseRequest $request): RedirectResponse
    {
        VasWarehouse::create([
            'business_id' => $this->businessId($request),
            'code' => strtoupper((string) $request->input('code')),
            'name' => $request->input('name'),
            'business_location_id' => $request->input('business_location_id'),
            'status' => $request->input('status', 'active'),
        ]);

        return redirect()
            ->route('vasaccounting.inventory.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.warehouse_saved')]);
    }

    public function storeDocument(StoreInventoryDocumentRequest $request): RedirectResponse
    {
        $document = $this->warehouseDocumentService->createDocument(
            $this->businessId($request),
            $request->validated(),
            (int) $request->user()->id
        );

        return redirect()
            ->route('vasaccounting.inventory.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.inventory_document_saved', ['document' => $document->document_no])]);
    }

    public function showDocument(Request $request, int $document)
    {
        $this->authorizePermission('vas_accounting.inventory.manage');

        $businessId = $this->businessId($request);
        $inventoryDocument = VasInventoryDocument::query()
            ->with([
                'warehouse.businessLocation',
                'destinationWarehouse.businessLocation',
                'offsetAccount',
                'period',
                'postedVoucher',
                'reversalVoucher',
                'lines.product',
            ])
            ->where('business_id', $businessId)
            ->findOrFail($document);

        $storageDocumentLinks = collect();
        $storageSyncLogs = collect();
        if (
            class_exists(\Modules\StorageManager\Entities\StorageDocumentLink::class)
            && Schema::hasTable('storage_document_links')
            && Schema::hasTable('storage_documents')
        ) {
            $storageDocumentLinks = \Modules\StorageManager\Entities\StorageDocumentLink::query()
                ->with('document')
                ->where('business_id', $businessId)
                ->where('linked_system', 'vas')
                ->where('linked_type', 'vas_inventory_document')
                ->where('linked_id', $inventoryDocument->id)
                ->orderByDesc('id')
                ->get();

            if (
                class_exists(\Modules\StorageManager\Entities\StorageSyncLog::class)
                && Schema::hasTable('storage_sync_logs')
                && $storageDocumentLinks->isNotEmpty()
            ) {
                $documentIds = $storageDocumentLinks->pluck('document_id')->filter()->unique()->values();
                $storageSyncLogs = \Modules\StorageManager\Entities\StorageSyncLog::query()
                    ->with(['createdByUser', 'document'])
                    ->where('business_id', $businessId)
                    ->whereIn('document_id', $documentIds)
                    ->orderByDesc('id')
                    ->limit(40)
                    ->get();
            }
        }

        $canViewStorageManager = (bool) optional($request->user())->can('storage_manager.view');
        $canOpenStorageDocument = $canViewStorageManager && Route::has('storage-manager.documents.show');
        $canManageDraftDelete = (bool) optional($request->user())->can('vas_accounting.inventory.destroy_draft');
        $deleteEligibility = $canManageDraftDelete
            ? $this->inventoryDocumentLifecycleUtil->deleteEligibility($inventoryDocument)
            : ['allowed' => false, 'reason' => null];

        return view('vasaccounting::inventory.show_document', [
            'inventoryDocument' => $inventoryDocument,
            'storageDocumentLinks' => $storageDocumentLinks,
            'storageSyncLogs' => $storageSyncLogs,
            'canOpenStorageDocument' => $canOpenStorageDocument,
            'can_admin_delete' => $canManageDraftDelete && (bool) ($deleteEligibility['allowed'] ?? false),
            'admin_delete_block_reason' => $canManageDraftDelete && ! ($deleteEligibility['allowed'] ?? false)
                ? (string) ($deleteEligibility['reason'] ?? __('vasaccounting::lang.inventory_document_delete_not_allowed'))
                : null,
            'admin_delete_route' => $canManageDraftDelete
                ? route('vasaccounting.inventory.documents.destroy', $inventoryDocument->id)
                : null,
        ]);
    }

    public function postDocument(Request $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.inventory.manage');

        $inventoryDocument = VasInventoryDocument::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($document);

        $this->warehouseDocumentService->postDocument($inventoryDocument, (int) $request->user()->id);

        return redirect()
            ->route('vasaccounting.inventory.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.inventory_document_posted')]);
    }

    public function reverseDocument(Request $request, int $document): RedirectResponse
    {
        $this->authorizePermission('vas_accounting.inventory.manage');

        $inventoryDocument = VasInventoryDocument::query()
            ->where('business_id', $this->businessId($request))
            ->findOrFail($document);

        try {
            $this->warehouseDocumentService->reverseDocument($inventoryDocument, (int) $request->user()->id);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('vasaccounting.inventory.documents.show', $inventoryDocument->id)
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('vasaccounting.inventory.documents.show', $inventoryDocument->id)
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }

        return redirect()
            ->route('vasaccounting.inventory.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.inventory_document_reversed')]);
    }

    public function destroyDocument(DestroyInventoryDocumentRequest $request, int $document): RedirectResponse
    {
        $inventoryDocument = $request->inventoryDocument();

        try {
            $this->inventoryDocumentLifecycleUtil->deleteDraftDocument($inventoryDocument, (int) $request->user()->id);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('vasaccounting.inventory.documents.show', $inventoryDocument->id)
                ->with('status', ['success' => false, 'msg' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('vasaccounting.inventory.documents.show', $inventoryDocument->id)
                ->with('status', ['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }

        return redirect()
            ->route('vasaccounting.inventory.index')
            ->with('status', ['success' => true, 'msg' => __('vasaccounting::lang.inventory_document_deleted')]);
    }
}
