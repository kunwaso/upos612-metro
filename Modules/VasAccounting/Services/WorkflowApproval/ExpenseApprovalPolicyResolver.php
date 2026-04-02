<?php

namespace Modules\VasAccounting\Services\WorkflowApproval;

use Modules\VasAccounting\Domain\FinanceCore\Models\FinanceDocument;

class ExpenseApprovalPolicyResolver
{
    public function resolve(FinanceDocument $document): ?array
    {
        if ($document->document_family !== 'expense_management') {
            return null;
        }

        $policyConfig = data_get(
            config('vasaccounting.approval_defaults.expense_document_policies', []),
            $document->document_type
        );

        if (! is_array($policyConfig) || $policyConfig === []) {
            return null;
        }

        $grossAmount = round((float) ($document->gross_amount ?? 0), 4);
        $makerChecker = (bool) ($policyConfig['maker_checker'] ?? true);
        $tiers = array_values(array_filter((array) ($policyConfig['tiers'] ?? []), 'is_array'));
        $resolvedTier = $this->resolveTier($tiers, $grossAmount);

        return [
            'policy_code' => (string) ($resolvedTier['policy_code'] ?? strtoupper((string) $document->document_type)),
            'maker_checker' => $makerChecker,
            'steps' => $this->normalizeSteps((array) ($resolvedTier['steps'] ?? [])),
            'threshold_max_amount' => array_key_exists('max_amount', $resolvedTier) && $resolvedTier['max_amount'] !== null
                ? round((float) $resolvedTier['max_amount'], 4)
                : null,
            'threshold_min_amount' => array_key_exists('min_amount', $resolvedTier) && $resolvedTier['min_amount'] !== null
                ? round((float) $resolvedTier['min_amount'], 4)
                : null,
        ];
    }

    protected function resolveTier(array $tiers, float $grossAmount): array
    {
        if ($tiers === []) {
            return [];
        }

        foreach ($tiers as $tier) {
            $minAmount = $tier['min_amount'] ?? null;
            $maxAmount = $tier['max_amount'] ?? null;
            $meetsMin = $minAmount === null || $grossAmount >= (float) $minAmount;
            $meetsMax = $maxAmount === null || $grossAmount <= (float) $maxAmount;

            if ($meetsMin && $meetsMax) {
                return $tier;
            }
        }

        return end($tiers) ?: [];
    }

    protected function normalizeSteps(array $steps): array
    {
        $normalized = [];

        foreach (array_values($steps) as $index => $step) {
            if (! is_array($step)) {
                continue;
            }

            $normalized[] = [
                'step_no' => (int) ($step['step_no'] ?? ($index + 1)),
                'approver_role' => $step['approver_role'] ?? null,
                'permission_code' => $step['permission_code'] ?? null,
                'label' => $step['label'] ?? null,
                'sla_hours' => array_key_exists('sla_hours', $step) && $step['sla_hours'] !== null
                    ? (int) $step['sla_hours']
                    : null,
                'warning_hours' => array_key_exists('warning_hours', $step) && $step['warning_hours'] !== null
                    ? (int) $step['warning_hours']
                    : null,
                'escalation_role' => $step['escalation_role'] ?? null,
            ];
        }

        return $normalized;
    }
}
