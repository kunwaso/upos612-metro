<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Facades\DB;
use Modules\VasAccounting\Entities\VasDocumentApproval;
use Modules\VasAccounting\Entities\VasDocumentAuditLog;
use Modules\VasAccounting\Entities\VasVoucher;
use RuntimeException;

class DocumentApprovalService
{
    public function __construct(protected ApprovalRuleService $approvalRuleService)
    {
    }

    public function submitVoucher(VasVoucher $voucher, int $userId, array $context = []): VasVoucher
    {
        if (! in_array($voucher->status, ['draft', 'approved'], true)) {
            throw new RuntimeException("Voucher [{$voucher->voucher_no}] cannot be submitted from status [{$voucher->status}].");
        }

        $approvalContext = $this->approvalContextForVoucher($voucher, $context);
        $documentFamily = $this->approvalRuleService->documentFamilyForContext($approvalContext);
        $requiresApproval = $this->approvalRuleService->requiresApproval((int) $voucher->business_id, $documentFamily, $approvalContext);
        $steps = $requiresApproval
            ? $this->approvalRuleService->stepsForContext((int) $voucher->business_id, $documentFamily, $approvalContext)
            : collect();
        $rule = $requiresApproval
            ? $this->approvalRuleService->resolveRule((int) $voucher->business_id, $documentFamily, $approvalContext)
            : null;

        return DB::transaction(function () use ($voucher, $userId, $documentFamily, $requiresApproval, $steps, $rule) {
            $openSteps = VasDocumentApproval::query()
                ->where('business_id', (int) $voucher->business_id)
                ->where('entity_type', VasVoucher::class)
                ->where('entity_id', (int) $voucher->id)
                ->whereIn('status', ['pending', 'queued'])
                ->get();

            foreach ($openSteps as $openStep) {
                $openStep->update([
                    'status' => 'replaced',
                    'acted_at' => now(),
                    'acted_by' => $userId,
                    'comments' => 'Superseded by resubmission.',
                ]);
            }

            if (! $requiresApproval || $steps->isEmpty()) {
                $before = $voucher->only(['status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'meta']);
                $voucher->status = 'approved';
                $voucher->submitted_at = now();
                $voucher->submitted_by = $userId;
                $voucher->approved_at = now();
                $voucher->approved_by = $userId;
                $voucher->meta = $this->mergeVoucherMeta((array) $voucher->meta, $documentFamily, false);
                $voucher->save();

                $this->recordAudit(
                    (int) $voucher->business_id,
                    VasVoucher::class,
                    (int) $voucher->id,
                    'auto_approved',
                    $userId,
                    $before,
                    $voucher->fresh()->only(['status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'meta'])
                );

                return $voucher->fresh();
            }

            foreach ($steps as $index => $step) {
                VasDocumentApproval::create([
                    'business_id' => (int) $voucher->business_id,
                    'entity_type' => VasVoucher::class,
                    'entity_id' => (int) $voucher->id,
                    'approval_rule_id' => $rule?->id,
                    'approval_rule_step_id' => $step->id,
                    'step_no' => (int) $step->step_no,
                    'assigned_to' => $step->approver_user_id,
                    'status' => $index === 0 ? 'pending' : 'queued',
                    'meta' => [
                        'document_family' => $documentFamily,
                        'approver_role' => $step->approver_role,
                        'permission_code' => $step->permission_code,
                    ],
                ]);
            }

            $before = $voucher->only(['status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'meta']);
            $voucher->status = 'pending_approval';
            $voucher->submitted_at = now();
            $voucher->submitted_by = $userId;
            $voucher->approved_at = null;
            $voucher->approved_by = null;
            $voucher->meta = $this->mergeVoucherMeta((array) $voucher->meta, $documentFamily, true);
            $voucher->save();

            $this->recordAudit(
                (int) $voucher->business_id,
                VasVoucher::class,
                (int) $voucher->id,
                'submitted_for_approval',
                $userId,
                $before,
                $voucher->fresh()->only(['status', 'submitted_at', 'submitted_by', 'approved_at', 'approved_by', 'meta'])
            );

            return $voucher->fresh();
        });
    }

    public function approveVoucher(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        return DB::transaction(function () use ($voucher, $userId, $comments) {
            $currentStep = VasDocumentApproval::query()
                ->where('business_id', (int) $voucher->business_id)
                ->where('entity_type', VasVoucher::class)
                ->where('entity_id', (int) $voucher->id)
                ->where('status', 'pending')
                ->orderBy('step_no')
                ->first();

            if (! $currentStep) {
                throw new RuntimeException("Voucher [{$voucher->voucher_no}] has no pending approval step.");
            }

            $currentStep->update([
                'status' => 'approved',
                'acted_by' => $userId,
                'acted_at' => now(),
                'comments' => $comments,
            ]);

            $nextStep = VasDocumentApproval::query()
                ->where('business_id', (int) $voucher->business_id)
                ->where('entity_type', VasVoucher::class)
                ->where('entity_id', (int) $voucher->id)
                ->where('status', 'queued')
                ->orderBy('step_no')
                ->first();

            $before = $voucher->only(['status', 'approved_at', 'approved_by']);
            if ($nextStep) {
                $nextStep->update(['status' => 'pending']);
            } else {
                $voucher->status = 'approved';
                $voucher->approved_at = now();
                $voucher->approved_by = $userId;
                $voucher->save();
            }

            $this->recordAudit(
                (int) $voucher->business_id,
                VasVoucher::class,
                (int) $voucher->id,
                $nextStep ? 'approval_step_completed' : 'approved',
                $userId,
                $before,
                $voucher->fresh()->only(['status', 'approved_at', 'approved_by'])
            );

            return $voucher->fresh();
        });
    }

    public function rejectVoucher(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        return DB::transaction(function () use ($voucher, $userId, $comments) {
            VasDocumentApproval::query()
                ->where('business_id', (int) $voucher->business_id)
                ->where('entity_type', VasVoucher::class)
                ->where('entity_id', (int) $voucher->id)
                ->whereIn('status', ['pending', 'queued'])
                ->get()
                ->each(function (VasDocumentApproval $step) use ($userId, $comments) {
                    $step->update([
                        'status' => $step->status === 'pending' ? 'rejected' : 'cancelled',
                        'acted_by' => $step->status === 'pending' ? $userId : $step->acted_by,
                        'acted_at' => $step->status === 'pending' ? now() : $step->acted_at,
                        'comments' => $step->status === 'pending' ? $comments : $step->comments,
                    ]);
                });

            $before = $voucher->only(['status', 'approved_at', 'approved_by', 'meta']);
            $voucher->status = 'draft';
            $voucher->approved_at = null;
            $voucher->approved_by = null;
            $voucher->meta = array_replace((array) $voucher->meta, [
                'approval' => array_replace((array) data_get((array) $voucher->meta, 'approval', []), [
                    'last_rejected_at' => now()->toDateTimeString(),
                    'last_rejected_by' => $userId,
                ]),
            ]);
            $voucher->save();

            $this->recordAudit(
                (int) $voucher->business_id,
                VasVoucher::class,
                (int) $voucher->id,
                'rejected',
                $userId,
                $before,
                $voucher->fresh()->only(['status', 'approved_at', 'approved_by', 'meta'])
            );

            return $voucher->fresh();
        });
    }

    public function cancelVoucher(VasVoucher $voucher, int $userId, ?string $comments = null): VasVoucher
    {
        if (in_array($voucher->status, ['posted', 'reversed'], true)) {
            throw new RuntimeException("Voucher [{$voucher->voucher_no}] cannot be cancelled from status [{$voucher->status}].");
        }

        return DB::transaction(function () use ($voucher, $userId, $comments) {
            VasDocumentApproval::query()
                ->where('business_id', (int) $voucher->business_id)
                ->where('entity_type', VasVoucher::class)
                ->where('entity_id', (int) $voucher->id)
                ->whereIn('status', ['pending', 'queued'])
                ->update([
                    'status' => 'cancelled',
                    'acted_by' => $userId,
                    'acted_at' => now(),
                    'comments' => $comments,
                ]);

            $before = $voucher->only(['status', 'cancelled_at', 'cancelled_by']);
            $voucher->status = 'cancelled';
            $voucher->cancelled_at = now();
            $voucher->cancelled_by = $userId;
            $voucher->save();

            $this->recordAudit(
                (int) $voucher->business_id,
                VasVoucher::class,
                (int) $voucher->id,
                'cancelled',
                $userId,
                $before,
                $voucher->fresh()->only(['status', 'cancelled_at', 'cancelled_by'])
            );

            return $voucher->fresh();
        });
    }

    public function canPostVoucher(VasVoucher $voucher): bool
    {
        if ($voucher->status === 'approved' || $voucher->status === 'posted') {
            return true;
        }

        if ($voucher->status !== 'draft') {
            return false;
        }

        $context = $this->approvalContextForVoucher($voucher);
        $documentFamily = $this->approvalRuleService->documentFamilyForContext($context);

        return ! $this->approvalRuleService->requiresApproval((int) $voucher->business_id, $documentFamily, $context);
    }

    public function recordAudit(
        int $businessId,
        string $entityType,
        int $entityId,
        string $action,
        ?int $userId = null,
        array $oldValues = [],
        array $newValues = []
    ): VasDocumentAuditLog {
        return VasDocumentAuditLog::create([
            'business_id' => $businessId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    protected function approvalContextForVoucher(VasVoucher $voucher, array $context = []): array
    {
        $meta = (array) $voucher->meta;

        return array_replace([
            'document_family' => data_get($meta, 'document_family'),
            'source_type' => $voucher->source_type,
            'module_area' => $voucher->module_area,
            'document_type' => $voucher->document_type,
            'business_location_id' => $voucher->business_location_id,
            'currency_code' => $voucher->currency_code,
            'amount' => max((float) $voucher->total_debit, (float) $voucher->total_credit),
            'requires_approval' => data_get($meta, 'lifecycle.requires_approval', data_get($meta, 'approval.requires_approval')),
        ], $context);
    }

    protected function mergeVoucherMeta(array $meta, string $documentFamily, bool $requiresApproval): array
    {
        $meta['document_family'] = $meta['document_family'] ?? $documentFamily;
        $meta['lifecycle'] = array_replace((array) data_get($meta, 'lifecycle', []), [
            'document_family' => $documentFamily,
            'requires_approval' => $requiresApproval,
        ]);
        $meta['approval'] = array_replace((array) data_get($meta, 'approval', []), [
            'requires_approval' => $requiresApproval,
        ]);

        return $meta;
    }
}
