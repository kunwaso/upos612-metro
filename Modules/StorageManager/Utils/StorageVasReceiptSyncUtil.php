<?php

namespace Modules\StorageManager\Utils;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\StorageManager\Entities\StorageSyncLog;
use Modules\VasAccounting\Entities\VasInventoryDocument;
use Modules\VasAccounting\Services\VasWarehouseDocumentService;
use RuntimeException;

class StorageVasReceiptSyncUtil
{
    public function __construct(
        protected VasWarehouseDocumentService $warehouseDocumentService
    ) {
    }

    public function unlinkReceiptVasSync(int $businessId, StorageDocument $receipt, int $userId): array
    {
        if ($receipt->document_type !== 'receipt') {
            throw new RuntimeException('Only receipt documents can be unlinked from VAS accounting.');
        }

        if (! in_array((string) $receipt->status, ['completed', 'closed'], true)) {
            throw new RuntimeException('Receipt must be completed or closed before unlinking accounting.');
        }

        if (! Schema::hasTable('vas_inventory_documents')) {
            throw new RuntimeException('VAS Accounting tables are not available.');
        }

        $vasDocumentId = $receipt->vas_inventory_document_id ? (int) $receipt->vas_inventory_document_id : null;
        $vasDocument = $vasDocumentId
            ? VasInventoryDocument::query()->where('business_id', $businessId)->find($vasDocumentId)
            : null;

        $actionTaken = 'none';

        if ($vasDocument) {
            if ((string) $vasDocument->status === 'posted') {
                $this->warehouseDocumentService->reverseDocument($vasDocument, $userId);
                $actionTaken = 'reversed';
            } elseif (in_array((string) $vasDocument->status, ['draft', 'pending_approval', 'approved'], true)) {
                DB::transaction(function () use ($vasDocument) {
                    $vasDocument->lines()->delete();
                    $vasDocument->delete();
                });
                $actionTaken = 'deleted_draft';
            } elseif ((string) $vasDocument->status === 'reversed') {
                $actionTaken = 'already_reversed';
            }
        }

        StorageDocumentLink::query()
            ->where('business_id', $businessId)
            ->where('document_id', $receipt->id)
            ->where('linked_system', 'vas')
            ->delete();

        $receipt->forceFill([
            'vas_inventory_document_id' => null,
            'sync_status' => 'not_required',
        ])->save();

        StorageSyncLog::query()->create([
            'business_id' => $businessId,
            'document_id' => $receipt->id,
            'linked_system' => 'vas',
            'action' => 'unlink',
            'status' => 'not_required',
            'message' => "VAS accounting link removed (action: {$actionTaken})"
                . ($vasDocumentId ? ", vas_inventory_document_id: {$vasDocumentId}" : ''),
            'payload' => [
                'vas_inventory_document_id' => $vasDocumentId,
                'action_taken' => $actionTaken,
            ],
            'created_by' => $userId,
        ]);

        return [
            'action_taken' => $actionTaken,
            'vas_inventory_document_id' => $vasDocumentId,
        ];
    }
}
