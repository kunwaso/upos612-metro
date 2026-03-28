<?php

namespace Modules\VasAccounting\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class CutoverService
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function cutoverSettings(int $businessId): array
    {
        if (! $this->hasSettingsColumn('cutover_settings')) {
            return $this->mergeCutoverSettings([]);
        }

        return $this->mergeCutoverSettings((array) $this->settingsModel($businessId)->cutover_settings);
    }

    public function rolloutSettings(int $businessId): array
    {
        if (! $this->hasSettingsColumn('rollout_settings')) {
            return $this->mergeRolloutSettings([]);
        }

        return $this->mergeRolloutSettings((array) $this->settingsModel($businessId)->rollout_settings);
    }

    public function mergeCutoverSettings(array $settings): array
    {
        $defaults = $this->vasUtil->defaultCutoverSettings();
        $merged = array_replace_recursive($defaults, $settings);
        $merged['hide_legacy_accounting_menu'] = (bool) ($merged['hide_legacy_accounting_menu'] ?? false);
        $merged['uat_statuses'] = array_replace(
            array_fill_keys(array_keys($this->cutoverPersonaDefinitions()), false),
            array_map(fn ($value) => (bool) $value, (array) ($merged['uat_statuses'] ?? []))
        );

        return $merged;
    }

    public function mergeRolloutSettings(array $settings): array
    {
        $defaults = $this->vasUtil->defaultRolloutSettings();
        $merged = array_replace_recursive($defaults, $settings);
        $merged['enabled_branch_ids'] = collect((array) ($merged['enabled_branch_ids'] ?? []))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->values()
            ->all();

        return $merged;
    }

    public function updateSettings(int $businessId, array $cutoverSettings, array $rolloutSettings): VasBusinessSetting
    {
        $settings = $this->settingsModel($businessId);

        if ($this->hasSettingsColumn('cutover_settings')) {
            $settings->cutover_settings = $this->mergeCutoverSettings(array_replace_recursive(
                (array) $settings->cutover_settings,
                $cutoverSettings
            ));
        }

        if ($this->hasSettingsColumn('rollout_settings')) {
            $settings->rollout_settings = $this->mergeRolloutSettings(array_replace_recursive(
                (array) $settings->rollout_settings,
                $rolloutSettings
            ));
        }

        if ($this->hasSettingsColumn('cutover_settings') || $this->hasSettingsColumn('rollout_settings')) {
            $settings->save();
        }

        return $settings->fresh();
    }

    public function updatePersonaStatus(int $businessId, string $personaKey, bool $completed): VasBusinessSetting
    {
        $definitions = $this->cutoverPersonaDefinitions();
        if (! array_key_exists($personaKey, $definitions)) {
            throw new \InvalidArgumentException("Unknown cutover persona [{$personaKey}].");
        }

        $settings = $this->settingsModel($businessId);
        if (! $this->hasSettingsColumn('cutover_settings')) {
            return $settings;
        }

        $cutoverSettings = $this->mergeCutoverSettings((array) $settings->cutover_settings);
        $cutoverSettings['uat_statuses'][$personaKey] = $completed;
        $cutoverSettings['last_parity_check_at'] = $cutoverSettings['last_parity_check_at'] ?: now()->toDateTimeString();
        $settings->cutover_settings = $cutoverSettings;
        $settings->save();

        return $settings->fresh();
    }

    public function cutoverPersonaDefinitions(): array
    {
        return (array) config('vasaccounting.cutover_uat_personas', []);
    }

    public function uatPersonas(int $businessId): array
    {
        $statuses = (array) ($this->cutoverSettings($businessId)['uat_statuses'] ?? []);

        return collect($this->cutoverPersonaDefinitions())
            ->map(function (array $definition, string $key) use ($statuses) {
                return $definition + [
                    'key' => $key,
                    'completed' => (bool) ($statuses[$key] ?? false),
                    'status_label' => (bool) ($statuses[$key] ?? false) ? 'Completed' : 'Pending',
                ];
            })
            ->values()
            ->all();
    }

    public function legacyRouteMappings(): array
    {
        return collect((array) config('vasaccounting.legacy_accounting_route_map', []))
            ->map(function (array $definition, string $key) {
                return $definition + [
                    'legacy_key' => $key,
                    'route_url' => $this->destinationUrl($definition),
                ];
            })
            ->values()
            ->all();
    }

    public function legacyModeOptions(): array
    {
        return [
            'observe' => 'Observe legacy routes',
            'redirect' => 'Redirect legacy routes to VAS',
            'disabled' => 'Disable legacy routes',
        ];
    }

    public function parallelRunOptions(): array
    {
        return [
            'not_started' => 'Not started',
            'in_progress' => 'In progress',
            'ready' => 'Ready for cutover',
            'cutover_complete' => 'Cutover complete',
        ];
    }

    public function rolloutStatusOptions(): array
    {
        return [
            'pilot' => 'Pilot',
            'staged' => 'Staged rollout',
            'full' => 'Full rollout',
        ];
    }

    public function legacyRoutesMode(int $businessId): string
    {
        return (string) ($this->cutoverSettings($businessId)['legacy_routes_mode'] ?? 'observe');
    }

    public function shouldHideLegacyAccountingMenu(int $businessId): bool
    {
        $settings = $this->cutoverSettings($businessId);

        return (bool) ($settings['hide_legacy_accounting_menu'] ?? false)
            || in_array((string) ($settings['legacy_routes_mode'] ?? 'observe'), ['redirect', 'disabled'], true);
    }

    public function legacyRouteAction(int $businessId, Request $request): ?array
    {
        $mode = $this->legacyRoutesMode($businessId);
        if (! in_array($mode, ['redirect', 'disabled'], true)) {
            return null;
        }

        $definition = $this->legacyRouteDefinitionForRequest($request);
        if (! is_array($definition)) {
            return null;
        }

        return [
            'mode' => $mode,
            'target_label' => (string) ($definition['target_label'] ?? 'VAS Accounting'),
            'target_url' => $this->destinationUrl($definition),
            'message' => __('vasaccounting::lang.legacy_accounting_redirected', [
                'target' => (string) ($definition['target_label'] ?? 'VAS Accounting'),
            ]),
        ];
    }

    public function readinessSummary(int $businessId): array
    {
        $cutoverSettings = $this->cutoverSettings($businessId);
        $rolloutSettings = $this->rolloutSettings($businessId);
        $personas = $this->uatPersonas($businessId);
        $completedPersonas = collect($personas)->where('completed', true)->count();
        $blockers = collect($this->cutoverBlockers($businessId));
        $activeBlockers = $blockers->where('count', '>', 0)->count();

        return [
            ['label' => 'Active blockers', 'value' => $activeBlockers],
            ['label' => 'Completed UAT personas', 'value' => $completedPersonas . '/' . count($personas)],
            ['label' => 'Legacy route mode', 'value' => $this->legacyModeLabel((string) $cutoverSettings['legacy_routes_mode'])],
            ['label' => 'Rollout status', 'value' => $this->rolloutStatusLabel((string) $rolloutSettings['status'])],
        ];
    }

    public function cutoverBlockers(int $businessId): array
    {
        $remainingPersonas = collect($this->uatPersonas($businessId))->where('completed', false)->count();

        return [
            ['label' => 'Draft vouchers', 'count' => $this->countWhere('vas_vouchers', ['business_id' => $businessId, 'status' => 'draft'])],
            ['label' => 'Pending approvals', 'count' => $this->countWhere('vas_vouchers', ['business_id' => $businessId, 'status' => 'pending_approval'])],
            ['label' => 'Posting failures', 'count' => $this->countNull('vas_posting_failures', 'resolved_at', ['business_id' => $businessId])],
            ['label' => 'Unmatched bank lines', 'count' => $this->countWhere('vas_bank_statement_lines', ['business_id' => $businessId, 'match_status' => 'unmatched'])],
            ['label' => 'Queued integrations', 'count' => $this->countIn('vas_integration_runs', 'status', ['queued', 'processing'], ['business_id' => $businessId])],
            ['label' => 'Pending UAT personas', 'count' => $remainingPersonas],
        ];
    }

    public function paritySnapshot(int $businessId): array
    {
        $legacyBalance = $this->legacyTreasuryBalance($businessId);
        $vasBalance = $this->vasTreasuryBalance($businessId);

        return [
            'legacy_accounts' => $this->countWhere('accounts', ['business_id' => $businessId]),
            'legacy_transactions' => $this->legacyAccountTransactionCount($businessId),
            'legacy_treasury_balance' => round($legacyBalance, 2),
            'vas_posted_vouchers' => $this->countWhere('vas_vouchers', ['business_id' => $businessId, 'status' => 'posted']),
            'vas_cash_bank_entries' => $this->vasCashBankEntryCount($businessId),
            'vas_treasury_balance' => round($vasBalance, 2),
            'balance_delta' => round($vasBalance - $legacyBalance, 2),
        ];
    }

    protected function settingsModel(int $businessId): VasBusinessSetting
    {
        return $this->vasUtil->getOrCreateBusinessSettings($businessId);
    }

    protected function hasSettingsColumn(string $column): bool
    {
        return Schema::hasTable('vas_business_settings')
            && Schema::hasColumn('vas_business_settings', $column);
    }

    protected function legacyRouteDefinitionForRequest(Request $request): ?array
    {
        $map = (array) config('vasaccounting.legacy_accounting_route_map', []);
        $firstSegment = (string) $request->segment(1);
        $lookupKey = $firstSegment === 'account-types'
            ? 'account-types'
            : (string) $request->segment(2);

        return $map[$lookupKey] ?? null;
    }

    protected function destinationUrl(array $definition): string
    {
        $url = route((string) $definition['route']);
        $query = (array) ($definition['query'] ?? []);

        if (empty($query)) {
            return $url;
        }

        return $url . '?' . http_build_query($query);
    }

    protected function legacyModeLabel(string $mode): string
    {
        return $this->legacyModeOptions()[$mode] ?? ucfirst($mode);
    }

    protected function rolloutStatusLabel(string $status): string
    {
        return $this->rolloutStatusOptions()[$status] ?? ucfirst($status);
    }

    protected function countWhere(string $table, array $where): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }

        return (int) $query->count();
    }

    protected function countNull(string $table, string $column, array $where = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table)->whereNull($column);
        foreach ($where as $whereColumn => $value) {
            $query->where($whereColumn, $value);
        }

        return (int) $query->count();
    }

    protected function countIn(string $table, string $column, array $values, array $where = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table)->whereIn($column, $values);
        foreach ($where as $whereColumn => $value) {
            $query->where($whereColumn, $value);
        }

        return (int) $query->count();
    }

    protected function legacyAccountTransactionCount(int $businessId): int
    {
        if (! Schema::hasTable('account_transactions') || ! Schema::hasTable('accounts')) {
            return 0;
        }

        return (int) DB::table('account_transactions as at')
            ->join('accounts as a', 'a.id', '=', 'at.account_id')
            ->where('a.business_id', $businessId)
            ->whereNull('at.deleted_at')
            ->count();
    }

    protected function legacyTreasuryBalance(int $businessId): float
    {
        if (! Schema::hasTable('account_transactions') || ! Schema::hasTable('accounts')) {
            return 0.0;
        }

        return (float) DB::table('account_transactions as at')
            ->join('accounts as a', 'a.id', '=', 'at.account_id')
            ->where('a.business_id', $businessId)
            ->whereNull('at.deleted_at')
            ->selectRaw("COALESCE(SUM(IF(at.type = 'credit', at.amount, -1 * at.amount)), 0) as balance")
            ->value('balance');
    }

    protected function vasCashBankEntryCount(int $businessId): int
    {
        if (! Schema::hasTable('vas_journal_entries') || ! Schema::hasTable('vas_accounts')) {
            return 0;
        }

        return (int) DB::table('vas_journal_entries as je')
            ->join('vas_accounts as a', 'a.id', '=', 'je.account_id')
            ->where('je.business_id', $businessId)
            ->where(function ($query) {
                $query->where('a.account_code', 'like', '111%')
                    ->orWhere('a.account_code', 'like', '112%');
            })
            ->count();
    }

    protected function vasTreasuryBalance(int $businessId): float
    {
        if (! Schema::hasTable('vas_journal_entries') || ! Schema::hasTable('vas_accounts')) {
            return 0.0;
        }

        return (float) DB::table('vas_journal_entries as je')
            ->join('vas_accounts as a', 'a.id', '=', 'je.account_id')
            ->where('je.business_id', $businessId)
            ->where(function ($query) {
                $query->where('a.account_code', 'like', '111%')
                    ->orWhere('a.account_code', 'like', '112%');
            })
            ->selectRaw('COALESCE(SUM(je.debit - je.credit), 0) as balance')
            ->value('balance');
    }
}
