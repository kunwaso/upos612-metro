<?php

namespace Modules\VasAccounting\Utils;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Entities\StorageDocumentLink;
use Modules\StorageManager\Entities\StorageSyncLog;
use Modules\VasAccounting\Entities\VasInventoryDocument;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Services\VasPostingService;
use RuntimeException;

class InventoryDocumentLifecycleUtil
{
    public function __construct(
        protected VasPostingService $postingService
    ) {
    }

    public function deleteEligibility(VasInventoryDocument $document): array
    {
        $document->loadMissing(['period', 'postedVoucher', 'reversalVoucher']);

        if (! in_array((string) $document->status, ['draft', 'pending_approval', 'approved'], true)) {
            if ((string) $document->status === 'posted') {
                return $this->blockedStatus(
                    'status_posted',
                    __('vasaccounting::lang.inventory_document_delete_posted_requires_reverse')
                );
            }

            return $this->blockedStatus(
                'status_blocked',
                __('vasaccounting::lang.inventory_document_delete_status_blocked', ['status' => (string) $document->status])
            );
        }

        $periodLockReason = $this->periodLockReason($document);
        if ($periodLockReason !== null) {
            return $this->blockedStatus('period_locked', $periodLockReason);
        }

        if (! empty($document->posted_voucher_id) || ! empty($document->reversal_voucher_id)) {
            return $this->blockedStatus(
                'voucher_linked',
                __('vasaccounting::lang.inventory_document_delete_gl_linked')
            );
        }

        if (! empty($document->posted_at) || ! empty($document->posted_by) || ! empty($document->reversed_at) || ! empty($document->reversed_by)) {
            return $this->blockedStatus(
                'already_processed',
                __('vasaccounting::lang.inventory_document_delete_gl_linked')
            );
        }

        foreach ($this->sourceVouchers($document) as $voucher) {
            if ((string) $voucher->status !== 'draft') {
                return $this->blockedStatus(
                    'source_voucher_processed',
                    __('vasaccounting::lang.inventory_document_delete_gl_linked')
                );
            }

            if (! empty($voucher->posted_at) || ! empty($voucher->posted_by) || ! empty($voucher->reversed_at) || ! empty($voucher->reversed_by)) {
                return $this->blockedStatus(
                    'source_voucher_processed',
                    __('vasaccounting::lang.inventory_document_delete_gl_linked')
                );
            }

            if ($voucher->journals()->exists()) {
                return $this->blockedStatus(
                    'source_voucher_journaled',
                    __('vasaccounting::lang.inventory_document_delete_gl_linked')
                );
            }
        }

        return [
            'allowed' => true,
            'code' => null,
            'reason' => null,
            'meta' => [],
        ];
    }

    public function deleteDraftDocument(VasInventoryDocument $document, int $userId): void
    {
        DB::transaction(function () use ($document, $userId) {
            $lockedDocument = VasInventoryDocument::query()
                ->where('business_id', (int) $document->business_id)
                ->lockForUpdate()
                ->findOrFail((int) $document->id);

            $eligibility = $this->deleteEligibility($lockedDocument);
            if (! ($eligibility['allowed'] ?? false)) {
                throw new RuntimeException((string) ($eligibility['reason'] ?? __('vasaccounting::lang.inventory_document_delete_not_allowed')));
            }

            $this->postingService->deleteDraftSourceVouchers(
                'inventory_document',
                (int) $lockedDocument->id,
                (int) $lockedDocument->business_id
            );

            $this->clearStorageLinks($lockedDocument, $userId);
            $lockedDocument->lines()->delete();

            $lockedDocument->delete();
        });
    }

    protected function sourceVouchers(VasInventoryDocument $document): Collection
    {
        return VasVoucher::query()
            ->where('business_id', (int) $document->business_id)
            ->where('source_type', 'inventory_document')
            ->where('source_id', (int) $document->id)
            ->orderByDesc('version_no')
            ->get();
    }

    protected function periodLockReason(VasInventoryDocument $document): ?string
    {
        $period = $document->period;
        if (! $period) {
            return null;
        }

        if (! in_array((string) $period->status, ['soft_locked', 'closed'], true)) {
            return null;
        }

        $periodLabel = (string) ($period->name ?: $period->label ?: $period->id);

        return "VAS accounting period [{$periodLabel}] is locked for posting.";
    }

    protected function clearStorageLinks(VasInventoryDocument $document, int $userId): void
    {
        if (
            class_exists(StorageDocumentLink::class)
            && Schema::hasTable('storage_document_links')
        ) {
            StorageDocumentLink::query()
                ->where('business_id', (int) $document->business_id)
                ->where('linked_system', 'vas')
                ->where('linked_type', 'vas_inventory_document')
                ->where('linked_id', (int) $document->id)
                ->delete();
        }

        if (
            ! class_exists(StorageDocument::class)
            || ! Schema::hasTable('storage_documents')
        ) {
            return;
        }

        $storageDocuments = StorageDocument::query()
            ->where('business_id', (int) $document->business_id)
            ->where('vas_inventory_document_id', (int) $document->id)
            ->get(['id', 'document_no']);

        if ($storageDocuments->isEmpty()) {
            return;
        }

        StorageDocument::query()
            ->whereIn('id', $storageDocuments->pluck('id'))
            ->update([
                'vas_inventory_document_id' => null,
                'sync_status' => 'not_required',
                'updated_at' => now(),
            ]);

        if (! class_exists(StorageSyncLog::class) || ! Schema::hasTable('storage_sync_logs')) {
            return;
        }

        $storageDocuments->each(function (StorageDocument $storageDocument) use ($document, $userId) {
            StorageSyncLog::query()->create([
                'business_id' => (int) $document->business_id,
                'document_id' => (int) $storageDocument->id,
                'linked_system' => 'vas',
                'action' => 'admin_delete_inventory_document',
                'status' => 'not_required',
                'message' => 'VAS inventory document '
                    . $document->document_no
                    . ' was deleted by admin; storage sync can be recreated.',
                'payload' => [
                    'vas_inventory_document_id' => (int) $document->id,
                    'vas_inventory_document_no' => (string) $document->document_no,
                ],
                'created_by' => $userId,
            ]);
        });
    }

    protected function blockedStatus(string $code, string $reason): array
    {
        return [
            'allowed' => false,
            'code' => $code,
            'reason' => $reason,
            'meta' => [],
        ];
    }
}
