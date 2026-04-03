<?php

namespace Modules\StorageManager\Http\Controllers;

use App\BusinessLocation;
use App\Http\Controllers\Controller;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Illuminate\Support\Facades\Route;

class StorageDocumentController extends Controller
{
    public function show(int $document)
    {
        if (! auth()->user()->can('storage_manager.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) request()->session()->get('user.business_id');

        $document = StorageDocument::query()
            ->with([
                'area',
                'parentDocument',
                'lines.product',
                'lines.variation',
                'lines.fromSlot',
                'lines.toSlot',
                'lines.tasks.assignee',
                'links' => fn ($query) => $query->orderByDesc('id'),
                'syncLogs' => fn ($query) => $query->with('createdByUser')->latest('id')->limit(10),
            ])
            ->where('business_id', $businessId)
            ->findOrFail($document);

        $locationName = (string) (BusinessLocation::query()
            ->where('business_id', $businessId)
            ->where('id', $document->location_id)
            ->value('name') ?: ('#' . $document->location_id));
        $linkedRecordActions = [];
        foreach ($document->links as $link) {
            $linkedRecordActions[(int) $link->id] = $this->resolveLinkedRecordAction($document, $link);
        }
        $vasLink = $document->links
            ->where('linked_system', 'vas')
            ->where('linked_type', 'vas_inventory_document')
            ->sortByDesc('id')
            ->first();
        $vasAction = $vasLink ? $this->resolveLinkedRecordAction($document, $vasLink) : null;

        return view('storagemanager::documents.show', [
            'document' => $document,
            'locationName' => $locationName,
            'workbench' => $this->resolveWorkbench($document),
            'linkedRecordActions' => $linkedRecordActions,
            'vasLink' => $vasLink,
            'vasAction' => $vasAction,
        ]);
    }

    protected function resolveWorkbench(StorageDocument $document): ?array
    {
        $route = null;
        $isModal = false;

        switch ((string) $document->document_type) {
            case 'purchase_requisition_advisory':
                $route = route('storage-manager.planning.show', $document->id);
                $isModal = true;
                break;
            case 'receipt':
                if (! empty($document->source_type) && ! empty($document->source_id)) {
                    $route = route('storage-manager.inbound.show', [
                        'sourceType' => $document->source_type,
                        'sourceId' => $document->source_id,
                    ]);
                }
                break;
            case 'putaway':
                $route = route('storage-manager.putaway.show', $document->id);
                break;
            case 'transfer_dispatch':
                if (! empty($document->source_id)) {
                    $route = route('storage-manager.transfers.dispatch.show', $document->source_id);
                }
                break;
            case 'transfer_receipt':
                if (! empty($document->source_id)) {
                    $route = route('storage-manager.transfers.receipts.show', $document->source_id);
                }
                break;
            case 'damage':
            case 'quarantine':
                $route = route('storage-manager.damage.show', $document->id);
                break;
            case 'cycle_count':
                $sessionId = (int) ($document->source_id ?: data_get($document->meta, 'count_session_id', 0));
                if ($sessionId > 0) {
                    $route = route('storage-manager.counts.show', $sessionId);
                }
                break;
            case 'replenishment':
                if (! empty($document->source_id)) {
                    $route = route('storage-manager.replenishment.show', $document->source_id);
                }
                break;
            case 'pick':
                if (! empty($document->source_id)) {
                    $route = route('storage-manager.outbound.pick.show', $document->source_id);
                }
                break;
            case 'pack':
                if (! empty($document->source_id)) {
                    $route = route('storage-manager.outbound.pack.show', $document->source_id);
                }
                break;
            case 'ship':
                if (! empty($document->source_id)) {
                    $route = route('storage-manager.outbound.ship.show', $document->source_id);
                }
                break;
        }

        if (empty($route)) {
            return null;
        }

        return [
            'url' => $route,
            'is_modal' => $isModal,
        ];
    }

    protected function resolveLinkedRecordAction(StorageDocument $document, StorageDocumentLink $link): ?array
    {
        $system = (string) $link->linked_system;
        $type = (string) $link->linked_type;
        $linkedId = (int) $link->linked_id;
        $url = null;
        $isModal = false;

        try {
            if ($system === 'app' && $type === 'purchase_requisition' && $linkedId > 0) {
                $url = action([\App\Http\Controllers\PurchaseRequisitionController::class, 'show'], [$linkedId]);
                $isModal = true;
            } elseif ($system === 'upos' && $type === 'purchase' && $linkedId > 0) {
                $url = action([\App\Http\Controllers\PurchaseController::class, 'show'], [$linkedId]);
                $isModal = true;
            } elseif ($system === 'upos' && $type === 'purchase_order' && $linkedId > 0) {
                $url = action([\App\Http\Controllers\PurchaseOrderController::class, 'show'], [$linkedId]);
                $isModal = true;
            } elseif ($system === 'upos' && $type === 'sales_order' && $linkedId > 0) {
                $url = action([\App\Http\Controllers\ProductSalesOrderController::class, 'show'], [$linkedId]);
                $isModal = true;
            } elseif ($system === 'upos' && $type === 'stock_transfer' && $linkedId > 0) {
                $url = action([\App\Http\Controllers\StockTransferController::class, 'show'], [$linkedId]);
                $isModal = true;
            } elseif ($system === 'source' && $type === 'transaction' && $linkedId > 0) {
                if (in_array((string) $document->document_type, ['damage', 'cycle_count'], true)) {
                    $url = action([\App\Http\Controllers\StockAdjustmentController::class, 'show'], [$linkedId]);
                    $isModal = true;
                }
            } elseif ($system === 'vas' && $type === 'vas_inventory_document') {
                if ($linkedId > 0 && Route::has('vasaccounting.inventory.documents.show')) {
                    $url = route('vasaccounting.inventory.documents.show', $linkedId);
                } elseif (Route::has('vasaccounting.inventory.index')) {
                    $url = route('vasaccounting.inventory.index');
                }
            }
        } catch (\Throwable $exception) {
            return null;
        }

        if (empty($url)) {
            return null;
        }

        return [
            'url' => $url,
            'is_modal' => $isModal,
        ];
    }
}
