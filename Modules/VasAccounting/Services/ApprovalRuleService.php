<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasApprovalRule;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class ApprovalRuleService
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function approvalDefaults(int $businessId): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);

        return array_replace_recursive(
            (array) config('vasaccounting.approval_defaults', []),
            (array) $settings->approval_settings
        );
    }

    public function documentFamilyForContext(array $context): string
    {
        if (! empty($context['document_family'])) {
            return (string) $context['document_family'];
        }

        $sourceType = (string) ($context['source_type'] ?? '');

        return (string) config("vasaccounting.document_family_by_source.{$sourceType}", 'manual');
    }

    public function defaultStatus(int $businessId, string $documentFamily, array $context = []): string
    {
        if (! empty($context['default_status'])) {
            return (string) $context['default_status'];
        }

        $defaults = $this->approvalDefaults($businessId);
        if ((string) ($context['source_type'] ?? '') === 'manual') {
            return (string) ($defaults['default_manual_voucher_status'] ?? 'draft');
        }

        return (string) data_get($defaults, "native_document_defaults.{$documentFamily}.default_status", 'draft');
    }

    public function requiresApproval(int $businessId, string $documentFamily, array $context = []): bool
    {
        if (array_key_exists('requires_approval', $context) && $context['requires_approval'] !== null) {
            return (bool) $context['requires_approval'];
        }

        $rule = $this->resolveRule($businessId, $documentFamily, $context);
        if ($rule) {
            $amount = round((float) ($context['amount'] ?? 0), 4);
            $autoApproveBelow = round((float) ($rule->auto_approve_below ?? 0), 4);
            if ($autoApproveBelow > 0 && $amount > 0 && $amount <= $autoApproveBelow) {
                return false;
            }

            return $rule->steps()->exists();
        }

        $defaults = $this->approvalDefaults($businessId);
        if ((string) ($context['source_type'] ?? '') === 'manual') {
            return (bool) ($defaults['require_manual_voucher_approval'] ?? false);
        }

        return (bool) data_get($defaults, "native_document_defaults.{$documentFamily}.requires_approval", false);
    }

    public function resolveRule(int $businessId, string $documentFamily, array $context = []): ?VasApprovalRule
    {
        if (! Schema::hasTable('vas_approval_rules') || ! Schema::hasTable('vas_approval_rule_steps')) {
            return null;
        }

        $amount = round((float) ($context['amount'] ?? 0), 4);
        $sourceType = $this->nullableString($context['source_type'] ?? null);
        $moduleArea = $this->nullableString($context['module_area'] ?? null);
        $documentType = $this->nullableString($context['document_type'] ?? null);
        $currencyCode = $this->nullableString($context['currency_code'] ?? null);
        $locationId = (int) ($context['business_location_id'] ?? 0);

        return VasApprovalRule::query()
            ->with('steps')
            ->where('business_id', $businessId)
            ->where('document_family', $documentFamily)
            ->where('is_active', true)
            ->get()
            ->filter(function (VasApprovalRule $rule) use ($amount, $sourceType, $moduleArea, $documentType, $currencyCode, $locationId, $context) {
                if ($rule->source_type && $rule->source_type !== $sourceType) {
                    return false;
                }

                if ($rule->module_area && $rule->module_area !== $moduleArea) {
                    return false;
                }

                if ($rule->document_type && $rule->document_type !== $documentType) {
                    return false;
                }

                if ($rule->currency_code && strtoupper((string) $rule->currency_code) !== strtoupper((string) $currencyCode)) {
                    return false;
                }

                if ($rule->business_location_id && (int) $rule->business_location_id !== $locationId) {
                    return false;
                }

                if ($rule->min_amount !== null && $amount < (float) $rule->min_amount) {
                    return false;
                }

                if ($rule->max_amount !== null && $amount > (float) $rule->max_amount) {
                    return false;
                }

                return $this->matchesConditions((array) $rule->conditions, $context);
            })
            ->sortByDesc(fn (VasApprovalRule $rule) => $this->specificityScore($rule))
            ->first();
    }

    public function stepsForContext(int $businessId, string $documentFamily, array $context = []): Collection
    {
        $rule = $this->resolveRule($businessId, $documentFamily, $context);

        return $rule ? $rule->steps : collect();
    }

    protected function matchesConditions(array $conditions, array $context): bool
    {
        foreach ($conditions as $key => $expected) {
            $actual = data_get($context, $key);

            if (is_array($expected)) {
                if (! in_array($actual, $expected, true)) {
                    return false;
                }

                continue;
            }

            if ($actual !== $expected) {
                return false;
            }
        }

        return true;
    }

    protected function specificityScore(VasApprovalRule $rule): int
    {
        $score = 0;

        foreach (['source_type', 'module_area', 'document_type', 'currency_code', 'business_location_id'] as $column) {
            if (! empty($rule->{$column})) {
                $score += 10;
            }
        }

        if ($rule->min_amount !== null) {
            $score += 5;
        }

        if ($rule->max_amount !== null) {
            $score += 5;
        }

        if (! empty($rule->conditions)) {
            $score += 5;
        }

        return $score;
    }

    protected function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
