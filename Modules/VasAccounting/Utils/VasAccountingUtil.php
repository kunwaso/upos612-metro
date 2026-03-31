<?php

namespace Modules\VasAccounting\Utils;

use App\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\VasAccounting\Entities\VasAccount;
use Modules\VasAccounting\Entities\VasAccountingPeriod;
use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Entities\VasDocumentSequence;
use Modules\VasAccounting\Entities\VasPostingFailure;
use Modules\VasAccounting\Entities\VasTaxCode;
use Modules\VasAccounting\Entities\VasVoucher;

class VasAccountingUtil
{
    public function businessIdFromRequest(Request $request): int
    {
        return (int) $request->session()->get('user.business_id');
    }

    public function getOrCreateBusinessSettings(int $businessId): VasBusinessSetting
    {
        $defaults = [
            'book_currency' => (string) config('vasaccounting.book_currency', 'VND'),
            'inventory_method' => (string) config('vasaccounting.inventory_method', 'weighted_average'),
            'is_enabled' => true,
            'posting_map' => $this->buildDefaultPostingMapByCodes($businessId),
            'compliance_settings' => [
                'standard' => 'Circular 99/2025/TT-BTC',
                'effective_date' => '2026-01-01',
            ],
            'inventory_settings' => [
                'method' => (string) config('vasaccounting.inventory_method', 'weighted_average'),
            ],
            'depreciation_settings' => [
                'method' => 'straight_line',
                'run_day_of_month' => 1,
            ],
            'tax_settings' => [
                'declaration_currency' => 'VND',
                'default_output_tax_code' => 'VAT10_OUT',
                'default_input_tax_code' => 'VAT10_IN',
            ],
            'einvoice_settings' => [
                'provider' => 'sandbox',
                'mode' => 'sandbox',
                'issue_on_post' => false,
            ],
            'report_preferences' => [
                'show_zero_balances' => false,
            ],
            'feature_flags' => $this->defaultFeatureFlags(),
            'approval_settings' => $this->defaultApprovalSettings(),
            'branch_settings' => [
                'dimension_key' => 'business_location_id',
                'use_business_locations_as_branches' => true,
            ],
            'integration_settings' => [
                'api_guard' => (string) config('vasaccounting.api_guard', 'auth:api'),
                'bank_statement_provider' => 'manual',
                'tax_export_provider' => 'local',
                'payroll_bridge_provider' => 'essentials',
            ],
            'budget_settings' => [
                'enforce_budget_control' => false,
                'default_budget_status' => 'draft',
            ],
        ];

        if (Schema::hasColumn('vas_business_settings', 'cutover_settings')) {
            $defaults['cutover_settings'] = $this->defaultCutoverSettings();
        }

        if (Schema::hasColumn('vas_business_settings', 'rollout_settings')) {
            $defaults['rollout_settings'] = $this->defaultRolloutSettings();
        }

        if (Schema::hasColumn('vas_business_settings', 'ui_settings')) {
            $defaults['ui_settings'] = ['locale' => $this->defaultVasLocale()];
        }

        return VasBusinessSetting::firstOrCreate(
            ['business_id' => $businessId],
            $defaults
        );
    }

    public function buildDefaultPostingMapByCodes(int $businessId): array
    {
        $codes = (array) config('vasaccounting.default_posting_map_codes', []);
        if (empty($codes)) {
            return [];
        }

        $accounts = VasAccount::query()
            ->where('business_id', $businessId)
            ->whereIn('account_code', array_values($codes))
            ->pluck('id', 'account_code');

        $map = [];
        foreach ($codes as $key => $code) {
            $map[$key] = $accounts[$code] ?? null;
        }

        return $map;
    }

    public function mandatoryPostingMapKeys(): array
    {
        return (array) config('vasaccounting.mandatory_posting_map_keys', []);
    }

    public function isPostingMapComplete(VasBusinessSetting $settings): bool
    {
        $postingMap = (array) $settings->posting_map;

        foreach ($this->mandatoryPostingMapKeys() as $requiredKey) {
            if (empty($postingMap[$requiredKey])) {
                return false;
            }
        }

        return true;
    }

    public function bootstrapBusiness(int $businessId, int $userId): array
    {
        $accounts = $this->seedChartOfAccounts($businessId, $userId);
        $taxCodes = $this->seedTaxCodes($businessId);
        $sequences = $this->seedDocumentSequences($businessId);
        $period = $this->createFiscalYearPeriodIfMissing($businessId);
        $settings = $this->getOrCreateBusinessSettings($businessId);
        $settings->posting_map = $this->buildDefaultPostingMapByCodes($businessId);
        $settings->save();

        return compact('accounts', 'taxCodes', 'sequences', 'period', 'settings');
    }

    public function bootstrapStatus(int $businessId): array
    {
        $accountCount = VasAccount::query()
            ->where('business_id', $businessId)
            ->count();

        $systemAccountCount = VasAccount::query()
            ->where('business_id', $businessId)
            ->where('is_system', true)
            ->count();

        $taxCodeCount = VasTaxCode::query()
            ->where('business_id', $businessId)
            ->count();

        $sequenceCount = VasDocumentSequence::query()
            ->where('business_id', $businessId)
            ->count();

        $periodCount = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->count();

        $settingsExists = VasBusinessSetting::query()
            ->where('business_id', $businessId)
            ->exists();

        return [
            'account_count' => $accountCount,
            'system_account_count' => $systemAccountCount,
            'manual_account_count' => max(0, $accountCount - $systemAccountCount),
            'tax_code_count' => $taxCodeCount,
            'sequence_count' => $sequenceCount,
            'period_count' => $periodCount,
            'settings_exists' => $settingsExists,
            'needs_bootstrap' => ! $settingsExists
                || $systemAccountCount === 0
                || $taxCodeCount === 0
                || $sequenceCount === 0
                || $periodCount === 0,
        ];
    }

    public function ensureBusinessBootstrapped(int $businessId, int $userId): array
    {
        $status = $this->bootstrapStatus($businessId);

        if (! $status['needs_bootstrap']) {
            return [
                'bootstrapped' => false,
                'status' => $status,
            ];
        }

        $result = $this->bootstrapBusiness($businessId, $userId);

        return [
            'bootstrapped' => true,
            'result' => $result,
            'status' => $this->bootstrapStatus($businessId),
        ];
    }

    public function seedChartOfAccounts(int $businessId, int $userId): Collection
    {
        $seedRows = collect((array) config('vasaccounting.vn_chart', []))
            ->sort(function (array $left, array $right) {
                return [$left['level'] ?? 1, strlen((string) $left['code']), (string) $left['code']]
                    <=>
                    [$right['level'] ?? 1, strlen((string) $right['code']), (string) $right['code']];
            })
            ->values();
        $created = collect();
        $parentCodes = $seedRows->pluck('parent_code')->filter()->unique()->values();
        $createdBy = $userId > 0 ? $userId : null;

        foreach ($seedRows as $row) {
            $parentId = null;
            if (! empty($row['parent_code'])) {
                $parentId = VasAccount::query()
                    ->where('business_id', $businessId)
                    ->where('account_code', $row['parent_code'])
                    ->value('id');
            }

            $account = VasAccount::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'account_code' => $row['code'],
                ],
                [
                    'account_name' => $row['name'],
                    'account_type' => $row['type'],
                    'account_category' => $row['category'] ?? null,
                    'normal_balance' => $row['normal_balance'] ?? 'debit',
                    'level' => $row['level'] ?? 1,
                    'parent_id' => $parentId,
                    'allows_manual_entries' => (bool) ($row['allows_manual_entries'] ?? ! $parentCodes->contains($row['code'])),
                    'is_control_account' => (bool) ($row['is_control_account'] ?? $parentCodes->contains($row['code'])),
                    'is_system' => true,
                    'is_active' => true,
                    'created_by' => $createdBy,
                    'meta' => [
                        'standard' => 'Circular 99/2025/TT-BTC',
                        'source_name' => $row['name'],
                        'parent_code' => $row['parent_code'] ?? null,
                        'seed_category' => $row['category'] ?? null,
                    ],
                ]
            );

            $created->push($account);
        }

        return $created;
    }

    public function seedTaxCodes(int $businessId): Collection
    {
        $created = collect();

        foreach ((array) config('vasaccounting.tax_codes', []) as $row) {
            $taxCode = VasTaxCode::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'code' => $row['code'],
                ],
                [
                    'name' => $row['name'],
                    'direction' => $row['direction'],
                    'rate' => $row['rate'],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $created->push($taxCode);
        }

        return $created;
    }

    public function seedDocumentSequences(int $businessId): Collection
    {
        $created = collect();

        foreach ((array) config('vasaccounting.voucher_sequences', []) as $row) {
            $sequence = VasDocumentSequence::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'sequence_key' => $row['sequence_key'],
                ],
                [
                    'prefix' => $row['prefix'],
                    'padding' => $row['padding'] ?? 5,
                    'next_number' => 1,
                    'reset_frequency' => 'yearly',
                    'is_active' => true,
                ]
            );

            $created->push($sequence);
        }

        return $created;
    }

    public function createFiscalYearPeriodIfMissing(int $businessId, ?Carbon $date = null): VasAccountingPeriod
    {
        $date = $date ?: Carbon::now(config('vasaccounting.default_timezone', 'Asia/Ho_Chi_Minh'));
        $business = Business::findOrFail($businessId);
        $fyStartMonth = max(1, min(12, (int) $business->fy_start_month));
        $year = (int) $date->year;
        $fiscalStart = Carbon::create($year, $fyStartMonth, 1, 0, 0, 0, $date->getTimezone());

        if ($date->lt($fiscalStart)) {
            $fiscalStart->subYear();
        }

        $fiscalEnd = (clone $fiscalStart)->addYear()->subDay();

        return VasAccountingPeriod::firstOrCreate(
            [
                'business_id' => $businessId,
                'start_date' => $fiscalStart->toDateString(),
                'end_date' => $fiscalEnd->toDateString(),
            ],
            [
                'name' => $fiscalStart->format('Y') . '-' . $fiscalEnd->format('Y') . ' Fiscal Year',
                'status' => 'open',
                'is_adjustment_period' => false,
            ]
        );
    }

    public function resolvePeriodForDate(int $businessId, Carbon $date): VasAccountingPeriod
    {
        $period = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->orderByDesc('start_date')
            ->first();

        if ($period) {
            return $period;
        }

        return $this->createFiscalYearPeriodIfMissing($businessId, $date);
    }

    public function defaultVasLocale(): string
    {
        return 'vi';
    }

    public function supportedLocales(): array
    {
        $locales = array_keys((array) config('constants.langs', []));

        return $locales === [] ? ['en', 'vi'] : $locales;
    }

    public function normalizeLocale(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));

        return in_array($locale, $this->supportedLocales(), true)
            ? $locale
            : $this->defaultVasLocale();
    }

    public function localeOptions(): array
    {
        return [
            'vi' => 'Tiếng Việt',
            'en' => 'English',
        ];
    }

    public function businessLocale(int $businessId): string
    {
        if (! Schema::hasColumn('vas_business_settings', 'ui_settings')) {
            return $this->defaultVasLocale();
        }

        $settings = $this->getOrCreateBusinessSettings($businessId);
        $locale = data_get((array) $settings->ui_settings, 'locale');

        return $this->normalizeLocale($locale);
    }

    public function resolveVasLocale(?int $businessId = null, ?Request $request = null): string
    {
        $request = $request ?: request();
        $queryLocale = $this->normalizeLocale($request?->query('lang'));

        if ($request?->filled('lang')) {
            return $queryLocale;
        }

        if (($businessId ?? 0) > 0) {
            return $this->businessLocale((int) $businessId);
        }

        $sessionLocale = $this->normalizeLocale((string) session('user.language', config('app.locale')));

        return $sessionLocale ?: $this->defaultVasLocale();
    }

    public function applyVasLocale(?int $businessId = null, ?Request $request = null): string
    {
        $locale = $this->resolveVasLocale($businessId, $request);
        app()->setLocale($locale);

        return $locale;
    }

    public function translationSlug(?string $value, string $fallback = 'item'): string
    {
        $slug = (string) Str::of($value ?: $fallback)
            ->snake()
            ->replace('__', '_')
            ->trim('_');

        return $slug !== '' ? $slug : $fallback;
    }

    public function pageTranslationKey(string $routeName): string
    {
        return str_replace('.', '_', (string) Str::after($routeName, 'vasaccounting.'));
    }

    public function translateWithFallback(string $key, ?string $fallback = null): string
    {
        $translated = __($key);

        return $translated === $key ? (string) $fallback : $translated;
    }

    public function fieldLabel(string $key, ?string $fallback = null): string
    {
        return $this->translateWithFallback("vasaccounting::lang.field_labels.{$key}", $fallback ?: Str::headline($key));
    }

    public function actionLabel(string $key, ?string $fallback = null): string
    {
        return $this->translateWithFallback("vasaccounting::lang.actions.{$key}", $fallback ?: Str::headline($key));
    }

    public function metricLabel(string $key, ?string $fallback = null): string
    {
        return $this->translateWithFallback("vasaccounting::lang.metrics.{$key}", $fallback ?: Str::headline($key));
    }

    public function uiLabel(string $key, ?string $fallback = null): string
    {
        return $this->translateWithFallback("vasaccounting::lang.ui.{$key}", $fallback ?: Str::headline($key));
    }

    public function emptyStateLabel(string $key, ?string $fallback = null): string
    {
        return $this->translateWithFallback("vasaccounting::lang.empty_states.{$key}", $fallback ?: Str::headline($key));
    }

    public function documentStatusLabel(?string $status): string
    {
        $status = (string) $status;

        return $this->translateWithFallback(
            "vasaccounting::lang.document_statuses.{$status}",
            Str::headline(str_replace('_', ' ', $status))
        );
    }

    public function periodStatusLabel(?string $status): string
    {
        $status = (string) $status;

        return $this->translateWithFallback(
            "vasaccounting::lang.period_statuses.{$status}",
            Str::headline(str_replace('_', ' ', $status))
        );
    }

    public function normalBalanceLabel(?string $balance): string
    {
        $balance = (string) $balance;

        return $this->translateWithFallback(
            "vasaccounting::lang.normal_balances.{$balance}",
            Str::headline($balance)
        );
    }

    public function matchStatusLabel(?string $status): string
    {
        $status = (string) $status;

        return $this->translateWithFallback(
            "vasaccounting::lang.match_statuses.{$status}",
            Str::headline(str_replace('_', ' ', $status))
        );
    }

    public function coverageStatusLabel(?string $status): string
    {
        $status = (string) $status;

        return $this->translateWithFallback(
            "vasaccounting::lang.coverage_statuses.{$status}",
            Str::headline(str_replace('_', ' ', $status))
        );
    }

    public function dueStatusLabel(?string $status): string
    {
        $status = (string) $status;

        return $this->translateWithFallback(
            "vasaccounting::lang.due_statuses.{$status}",
            Str::headline(str_replace('_', ' ', $status))
        );
    }

    public function genericStatusLabel(?string $status): string
    {
        $status = (string) $status;

        return $this->translateWithFallback(
            "vasaccounting::lang.generic_statuses.{$status}",
            Str::headline(str_replace('_', ' ', $status))
        );
    }

    public function moduleAreaLabel(?string $area): string
    {
        $area = (string) $area;

        return $this->translateWithFallback(
            "vasaccounting::lang.module_areas.{$area}",
            Str::headline(str_replace('_', ' ', $area))
        );
    }

    public function documentTypeLabel(?string $type): string
    {
        $type = (string) $type;

        return $this->translateWithFallback(
            "vasaccounting::lang.document_types.{$type}",
            Str::headline(str_replace('_', ' ', $type))
        );
    }

    public function voucherTypeLabel(?string $type): string
    {
        $type = (string) $type;

        return $this->translateWithFallback(
            "vasaccounting::lang.voucher_types.{$type}",
            Str::headline(str_replace('_', ' ', $type))
        );
    }

    public function reportKeyLabel(?string $key): string
    {
        $key = (string) $key;

        return $this->translateWithFallback(
            "vasaccounting::lang.report_keys.{$key}",
            Str::headline(str_replace('_', ' ', $key))
        );
    }

    public function postingMapLabel(?string $key): string
    {
        $key = (string) $key;

        return $this->translateWithFallback(
            "vasaccounting::lang.posting_map_keys.{$key}",
            Str::headline(str_replace('_', ' ', $key))
        );
    }

    public function providerLabel(string $provider, ?string $providerConfigKey = null): string
    {
        $provider = (string) $provider;
        $family = match ($providerConfigKey) {
            'bank_statement_import_adapters' => 'bank_statement_import',
            'tax_export_adapters' => 'tax_export',
            'einvoice_adapters' => 'einvoice',
            'payroll_bridge_adapters' => 'payroll_bridge',
            default => null,
        };

        if ($family) {
            $translated = $this->translateWithFallback("vasaccounting::lang.providers.{$family}.{$provider}", '');
            if ($translated !== '') {
                return $translated;
            }
        }

        return $this->translateWithFallback(
            "vasaccounting::lang.providers.generic.{$provider}",
            Str::headline(str_replace('_', ' ', $provider))
        );
    }

    public function providerOptions(string $providerConfigKey): array
    {
        return collect((array) config("vasaccounting.{$providerConfigKey}", []))
            ->keys()
            ->mapWithKeys(fn (string $provider) => [$provider => $this->providerLabel($provider, $providerConfigKey)])
            ->all();
    }

    protected function translateQuickActions(string $pageKey, array $quickActions): array
    {
        return collect($quickActions)
            ->map(function (array $action) use ($pageKey) {
                $actionKey = (string) ($action['action_key'] ?? $this->translationSlug($action['label'] ?? data_get($action, 'route', 'action')));

                $action['label'] = $this->translateWithFallback(
                    "vasaccounting::lang.pages.{$pageKey}.quick_actions.{$actionKey}",
                    $action['label'] ?? Str::headline($actionKey)
                );

                return $action;
            })
            ->values()
            ->all();
    }

    protected function translatePageConfig(string $routeName, array $meta): array
    {
        $pageKey = $this->pageTranslationKey($routeName);
        $meta['title'] = $this->translateWithFallback("vasaccounting::lang.pages.{$pageKey}.title", $meta['title'] ?? Str::headline($routeName));
        $meta['nav_label'] = $this->translateWithFallback("vasaccounting::lang.pages.{$pageKey}.nav_label", $meta['nav_label'] ?? $meta['title']);
        $meta['subtitle'] = $this->translateWithFallback("vasaccounting::lang.pages.{$pageKey}.subtitle", $meta['subtitle'] ?? null);
        $meta['quick_actions'] = $this->translateQuickActions($pageKey, (array) ($meta['quick_actions'] ?? []));

        return $meta;
    }

    protected function translateDomainConfig(string $domain, array $config): array
    {
        $config['title'] = $this->translateWithFallback("vasaccounting::lang.domains.{$domain}.title", $config['title'] ?? Str::headline($domain));
        $config['nav_label'] = $this->translateWithFallback("vasaccounting::lang.domains.{$domain}.nav_label", $config['nav_label'] ?? $config['title']);
        $config['subtitle'] = $this->translateWithFallback("vasaccounting::lang.domains.{$domain}.subtitle", $config['subtitle'] ?? null);
        $config['record_label'] = $this->translateWithFallback("vasaccounting::lang.domains.{$domain}.record_label", $config['record_label'] ?? null);

        return $config;
    }

    public function documentStatuses(): array
    {
        return collect(array_keys((array) config('vasaccounting.document_statuses', [])))
            ->mapWithKeys(fn (string $status) => [$status => $this->documentStatusLabel($status)])
            ->all();
    }

    public function periodStatuses(): array
    {
        return collect(array_keys((array) config('vasaccounting.period_statuses', [])))
            ->mapWithKeys(fn (string $status) => [$status => $this->periodStatusLabel($status)])
            ->all();
    }

    public function defaultFeatureFlags(): array
    {
        return (array) config('vasaccounting.feature_flags', []);
    }

    public function defaultApprovalSettings(): array
    {
        return (array) config('vasaccounting.approval_defaults', []);
    }

    public function enterpriseDomains(): array
    {
        return collect((array) config('vasaccounting.enterprise_domains', []))
            ->mapWithKeys(fn (array $config, string $domain) => [$domain => $this->translateDomainConfig($domain, $config)])
            ->all();
    }

    public function pageSections(): array
    {
        return collect((array) config('vasaccounting.page_sections', []))
            ->mapWithKeys(function (array $meta, string $sectionKey) {
                $meta['label'] = $this->translateWithFallback(
                    "vasaccounting::lang.sections.{$sectionKey}",
                    $meta['label'] ?? Str::headline($sectionKey)
                );

                return [$sectionKey => $meta];
            })
            ->all();
    }

    public function pageRegistry(): array
    {
        return collect((array) config('vasaccounting.page_registry', []))
            ->mapWithKeys(fn (array $meta, string $routeName) => [$routeName => $this->translatePageConfig($routeName, $meta)])
            ->all();
    }

    public function defaultCutoverSettings(): array
    {
        $defaults = (array) config('vasaccounting.cutover_defaults', []);
        $defaults['hide_legacy_accounting_menu'] = (bool) ($defaults['hide_legacy_accounting_menu'] ?? false);
        $defaults['uat_statuses'] = array_replace(
            array_fill_keys(array_keys((array) config('vasaccounting.cutover_uat_personas', [])), false),
            (array) ($defaults['uat_statuses'] ?? [])
        );

        return $defaults;
    }

    public function defaultRolloutSettings(): array
    {
        $defaults = (array) config('vasaccounting.rollout_defaults', []);
        $defaults['enabled_branch_ids'] = array_values(array_filter(
            array_map('intval', (array) ($defaults['enabled_branch_ids'] ?? [])),
            fn (int $value) => $value > 0
        ));
        $defaults['enabled_document_families'] = array_values(array_filter(
            array_map('strval', (array) ($defaults['enabled_document_families'] ?? [])),
            fn (string $value) => $value !== ''
        ));

        return $defaults;
    }

    public function familyModeOptions(): array
    {
        return (array) config('vasaccounting.family_mode_options', []);
    }

    public function nativeDocumentFamilies(): array
    {
        return (array) config('vasaccounting.native_document_families', []);
    }

    public function documentFamilyBySource(string $sourceType): string
    {
        return (string) config("vasaccounting.document_family_by_source.{$sourceType}", 'manual');
    }

    public function enterpriseDomainConfig(string $domain): array
    {
        $config = $this->enterpriseDomains()[$domain] ?? null;
        if (! is_array($config)) {
            throw new InvalidArgumentException("Unknown VAS enterprise domain [{$domain}].");
        }

        return $config;
    }

    public function businessContext(int $businessId): array
    {
        $business = Business::query()
            ->select(['id', 'name'])
            ->find($businessId);

        return [
            'id' => $businessId,
            'label' => $business?->name ?: (($this->resolveVasLocale($businessId) === 'vi' ? 'Doanh nghiệp #' : 'Business #') . $businessId),
        ];
    }

    public function currentPeriodContext(int $businessId): ?array
    {
        $today = now()->toDateString();
        $period = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first();

        if (! $period) {
            $period = VasAccountingPeriod::query()
                ->where('business_id', $businessId)
                ->orderByDesc('start_date')
                ->first();
        }

        if (! $period) {
            return null;
        }

        return [
            'name' => $this->localizedPeriodName($period->name),
            'status' => $period->status,
            'status_label' => $this->periodStatusLabel((string) $period->status),
            'start_date' => $period->start_date,
            'end_date' => $period->end_date,
        ];
    }

    public function localizedPeriodName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        if (preg_match('/^(?<start>\d{4})-(?<end>\d{4}) Fiscal Year$/', $name, $matches) === 1) {
            $translated = __('vasaccounting::lang.period_names.fiscal_year_range', [
                'start' => $matches['start'],
                'end' => $matches['end'],
            ]);

            return $translated === 'vasaccounting::lang.period_names.fiscal_year_range'
                ? $name
                : $translated;
        }

        return $name;
    }

    public function pageMeta(?string $routeName, int $businessId): ?array
    {
        if (empty($routeName)) {
            return null;
        }

        $registry = $this->pageRegistry();
        $meta = $registry[$routeName] ?? null;
        if (! is_array($meta)) {
            return null;
        }

        $settings = $this->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->defaultFeatureFlags(), (array) $settings->feature_flags);
        $featureFlag = (string) ($meta['feature_flag'] ?? '');
        if ($featureFlag !== '' && ($featureFlags[$featureFlag] ?? true) === false) {
            return null;
        }

        $sectionKey = (string) ($meta['section_group'] ?? 'controls');
        $section = (array) ($this->pageSections()[$sectionKey] ?? []);
        $quickActions = collect((array) ($meta['quick_actions'] ?? []))
            ->filter(function (array $action) use ($featureFlags) {
                $actionFeatureFlag = (string) ($action['feature_flag'] ?? '');
                if ($actionFeatureFlag !== '' && ($featureFlags[$actionFeatureFlag] ?? true) === false) {
                    return false;
                }

                $permission = (string) ($action['permission'] ?? '');

                return $permission === '' || auth()->user()?->can($permission);
            })
            ->values()
            ->all();

        return array_replace([
            'route' => $routeName,
            'section_group' => $sectionKey,
            'section_label' => (string) ($section['label'] ?? Str::headline($sectionKey)),
            'badge_variant' => (string) ($section['badge_variant'] ?? 'light-primary'),
            'nav_label' => $meta['title'] ?? Str::headline($routeName),
            'supports_location_filter' => false,
            'show_in_nav' => false,
            'quick_actions' => [],
            'icon' => 'ki-outline ki-chart-simple-2',
        ], $meta, [
            'quick_actions' => $quickActions,
        ]);
    }

    public function navigationItems(int $businessId): array
    {
        $settings = $this->getOrCreateBusinessSettings($businessId);
        $featureFlags = array_replace($this->defaultFeatureFlags(), (array) $settings->feature_flags);
        $sectionOrder = array_values(array_keys($this->pageSections()));
        $items = collect($this->pageRegistry())
            ->map(function (array $meta, string $routeName) use ($featureFlags) {
                if (! ($meta['show_in_nav'] ?? false)) {
                    return null;
                }

                $featureFlag = (string) ($meta['feature_flag'] ?? '');
                if ($featureFlag !== '' && ($featureFlags[$featureFlag] ?? true) === false) {
                    return null;
                }

                return [
                    'route' => $routeName,
                    'label' => (string) ($meta['nav_label'] ?? $meta['title'] ?? Str::headline($routeName)),
                    'permission' => (string) ($meta['permission'] ?? ''),
                    'active' => (string) ($meta['active_pattern'] ?? $routeName),
                    'section_group' => (string) ($meta['section_group'] ?? 'controls'),
                    'icon' => (string) ($meta['icon'] ?? 'ki-outline ki-chart-simple-2'),
                    'sort' => (int) ($meta['nav_sort'] ?? 999),
                ];
            })
            ->filter()
            ->sort(function (array $left, array $right) use ($sectionOrder) {
                $leftSection = array_search($left['section_group'], $sectionOrder, true);
                $rightSection = array_search($right['section_group'], $sectionOrder, true);

                return [$leftSection === false ? 999 : $leftSection, $left['sort'], $left['label']]
                    <=> [$rightSection === false ? 999 : $rightSection, $right['sort'], $right['label']];
            })
            ->values()
            ->map(function (array $item) {
                unset($item['sort']);

                return $item;
            })
            ->all();

        return $items;
    }

    public function navigationGroups(int $businessId): array
    {
        $items = collect($this->navigationItems($businessId));
        if ($items->isEmpty()) {
            return [];
        }

        $sectionDefinitions = $this->pageSections();
        $groups = [];

        foreach ($sectionDefinitions as $sectionKey => $sectionMeta) {
            $sectionItems = $items->where('section_group', $sectionKey)->values()->all();
            if ($sectionItems === []) {
                continue;
            }

            $groups[] = [
                'key' => $sectionKey,
                'label' => (string) ($sectionMeta['label'] ?? Str::headline($sectionKey)),
                'badge_variant' => (string) ($sectionMeta['badge_variant'] ?? 'light-primary'),
                'items' => $sectionItems,
            ];
        }

        return $groups;
    }

    public function enterpriseDomainSummary(int $businessId, string $domain): array
    {
        $config = $this->enterpriseDomainConfig($domain);
        $recordTable = (string) ($config['record_table'] ?? '');
        $recordLabel = (string) ($config['record_label'] ?? 'Records');
        $providerConfigKey = (string) ($config['provider_config_key'] ?? '');

        $records = 0;
        if ($recordTable !== '' && Schema::hasTable($recordTable)) {
            $query = DB::table($recordTable)->where('business_id', $businessId);

            if ($recordTable === 'vas_vouchers') {
                $query->where('module_area', $domain);
            }

            $records = (int) $query->count();
        }

        $postedVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('module_area', $domain)
            ->where('status', 'posted')
            ->count();

        $workflowBacklog = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('module_area', $domain)
            ->whereIn('status', ['draft', 'pending_approval', 'approved'])
            ->count();

        $providerCount = $providerConfigKey !== ''
            ? count((array) config("vasaccounting.{$providerConfigKey}", []))
            : 0;

        return [
            'record_label' => $recordLabel,
            'records' => $records,
            'posted_vouchers' => $postedVouchers,
            'workflow_backlog' => $workflowBacklog,
            'provider_count' => $providerCount,
        ];
    }

    public function chartOptions(int $businessId): Collection
    {
        return VasAccount::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'level']);
    }

    public function dashboardMetrics(int $businessId): array
    {
        $openPeriods = VasAccountingPeriod::query()
            ->where('business_id', $businessId)
            ->where('status', 'open')
            ->count();

        $postingFailures = VasPostingFailure::query()
            ->where('business_id', $businessId)
            ->whereNull('resolved_at')
            ->count();

        $draftVouchers = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('status', 'draft')
            ->count();

        $postedThisMonth = VasVoucher::query()
            ->where('business_id', $businessId)
            ->where('status', 'posted')
            ->whereMonth('posted_at', now()->month)
            ->whereYear('posted_at', now()->year)
            ->count();

        return compact('openPeriods', 'postingFailures', 'draftVouchers', 'postedThisMonth');
    }
}
