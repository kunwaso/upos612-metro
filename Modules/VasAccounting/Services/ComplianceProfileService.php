<?php

namespace Modules\VasAccounting\Services;

use Modules\VasAccounting\Entities\VasBusinessSetting;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;

class ComplianceProfileService
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function availableProfiles(): array
    {
        return (array) config('vasaccounting.compliance_profiles.profiles', []);
    }

    public function defaultProfileKey(): string
    {
        $default = (string) config('vasaccounting.compliance_profiles.default', 'tt99_2025');

        return array_key_exists($default, $this->availableProfiles()) ? $default : 'tt99_2025';
    }

    public function activeProfileForBusiness(int $businessId): array
    {
        return $this->activeProfileForSettings($this->vasUtil->getOrCreateBusinessSettings($businessId));
    }

    public function activeProfileForSettings(VasBusinessSetting $settings): array
    {
        $settingsPayload = (array) $settings->compliance_settings;
        $profileKey = $this->normalizeProfileKey((string) (
            $settings->compliance_standard
            ?: ($settingsPayload['standard'] ?? $this->defaultProfileKey())
        ));
        $profile = $this->availableProfiles()[$profileKey] ?? null;

        if (! is_array($profile)) {
            $profileKey = $this->defaultProfileKey();
            $profile = (array) ($this->availableProfiles()[$profileKey] ?? []);
        }

        $effectiveDate = (string) (
            optional($settings->compliance_effective_date)->toDateString()
            ?: ($settingsPayload['effective_date'] ?? ($profile['effective_date'] ?? '2026-01-01'))
        );
        $legacyBridgeEnabled = (bool) (
            $settings->compliance_legacy_bridge_enabled
            || ($settingsPayload['legacy_bridge_enabled'] ?? false)
        );

        if (! ((bool) ($profile['legacy_bridge_allowed'] ?? true))) {
            $legacyBridgeEnabled = false;
        }
        $profileVersion = (string) (
            $settings->compliance_profile_version
            ?: ($settingsPayload['profile_version'] ?? '2026.01')
        );

        return array_replace($profile, [
            'key' => $profileKey,
            'effective_date' => $effectiveDate,
            'legacy_bridge_enabled' => $legacyBridgeEnabled,
            'profile_version' => $profileVersion,
        ]);
    }

    public function requiredPostingMapKeys(VasBusinessSetting $settings): array
    {
        $profile = $this->activeProfileForSettings($settings);

        return array_values(array_unique((array) ($profile['required_posting_map_keys'] ?? config('vasaccounting.mandatory_posting_map_keys', []))));
    }

    public function requiredStatementTypes(VasBusinessSetting $settings): array
    {
        $profile = $this->activeProfileForSettings($settings);

        return array_values(array_unique((array) ($profile['required_statement_types'] ?? array_keys((array) config('vasaccounting.financial_statement_types', [])))));
    }

    public function validateSetupPayload(array $payload): void
    {
        $settings = (array) ($payload['compliance_settings'] ?? []);
        $standard = $this->normalizeProfileKey((string) ($settings['standard'] ?? $this->defaultProfileKey()));
        if (! array_key_exists($standard, $this->availableProfiles())) {
            throw new RuntimeException("Unsupported compliance profile [{$standard}].");
        }

        $effectiveDate = (string) ($settings['effective_date'] ?? '');
        if ($effectiveDate !== '' && $standard === 'tt99_2025' && $effectiveDate < '2026-01-01') {
            throw new RuntimeException('TT99 compliance profile requires effective date on or after 2026-01-01.');
        }

        $profile = (array) ($this->availableProfiles()[$standard] ?? []);
        $legacyBridgeEnabled = (bool) ($settings['legacy_bridge_enabled'] ?? false);
        if ($legacyBridgeEnabled && ! ((bool) ($profile['legacy_bridge_allowed'] ?? true))) {
            throw new RuntimeException("Compliance profile [{$standard}] does not allow legacy bridge mode.");
        }
    }

    protected function normalizeProfileKey(string $value): string
    {
        $value = trim(strtolower($value));

        return match ($value) {
            'tt99_2025', 'circular 99/2025/tt-btc', '99/2025/tt-btc' => 'tt99_2025',
            'tt200_legacy_bridge', 'tt200', 'circular 200/2014/tt-btc', '200/2014/tt-btc' => 'tt200_legacy_bridge',
            default => $value,
        };
    }
}
