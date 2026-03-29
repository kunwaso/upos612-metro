<?php

namespace Modules\VasAccounting\Services;

use Modules\VasAccounting\Utils\VasAccountingUtil;

class ProviderHealthService
{
    public function __construct(protected VasAccountingUtil $vasUtil)
    {
    }

    public function healthForBusiness(int $businessId): array
    {
        $settings = $this->vasUtil->getOrCreateBusinessSettings($businessId);
        $integrationSettings = (array) $settings->integration_settings;
        $einvoiceSettings = (array) $settings->einvoice_settings;

        return [
            $this->domainHealth('bank_statement_import', (string) ($integrationSettings['bank_statement_provider'] ?? 'manual')),
            $this->domainHealth('tax_export', (string) ($integrationSettings['tax_export_provider'] ?? 'local')),
            $this->domainHealth('einvoice', (string) ($einvoiceSettings['provider'] ?? 'sandbox')),
            $this->domainHealth('payroll_bridge', (string) ($integrationSettings['payroll_bridge_provider'] ?? 'essentials')),
        ];
    }

    protected function domainHealth(string $domainKey, string $provider): array
    {
        $adapterMap = (array) config("vasaccounting.{$domainKey}_adapters", []);
        $profile = (array) config("vasaccounting.provider_health_profiles.{$domainKey}.{$provider}", []);
        $requiredConfig = (array) ($profile['required_config'] ?? []);

        $missingConfig = [];
        foreach ($requiredConfig as $configPath) {
            $value = config($configPath);
            if ($value === null || $value === '') {
                $missingConfig[] = $configPath;
            }
        }

        $adapterRegistered = array_key_exists($provider, $adapterMap);
        $productionReady = (bool) ($profile['production_ready'] ?? false);
        $ready = $adapterRegistered && $productionReady && empty($missingConfig);

        return [
            'domain' => $domainKey,
            'provider' => $provider,
            'label' => (string) ($profile['label'] ?? ucfirst(str_replace('_', ' ', $provider))),
            'adapter_registered' => $adapterRegistered,
            'production_ready' => $productionReady,
            'ready' => $ready,
            'missing_config' => $missingConfig,
            'notes' => (string) ($profile['notes'] ?? ''),
        ];
    }
}
