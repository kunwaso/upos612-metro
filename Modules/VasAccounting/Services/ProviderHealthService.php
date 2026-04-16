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
            $this->domainHealth('bank_statement_import', (string) ($integrationSettings['bank_statement_provider'] ?? 'manual'), $integrationSettings, $einvoiceSettings),
            $this->domainHealth('tax_export', (string) ($integrationSettings['tax_export_provider'] ?? 'local'), $integrationSettings, $einvoiceSettings),
            $this->domainHealth('einvoice', (string) ($einvoiceSettings['provider'] ?? 'sandbox'), $integrationSettings, $einvoiceSettings),
            $this->domainHealth('payroll_bridge', (string) ($integrationSettings['payroll_bridge_provider'] ?? 'essentials'), $integrationSettings, $einvoiceSettings),
        ];
    }

    protected function domainHealth(string $domainKey, string $provider, array $integrationSettings, array $einvoiceSettings): array
    {
        $adapterMap = (array) config("vasaccounting.{$domainKey}_adapters", []);
        $profile = (array) config("vasaccounting.provider_health_profiles.{$domainKey}.{$provider}", []);
        $requiredConfig = (array) ($profile['required_config'] ?? []);

        $missingConfig = [];
        foreach ($requiredConfig as $configPath) {
            $value = null;
            if (str_starts_with($configPath, 'integration_settings.')) {
                $value = data_get($integrationSettings, substr($configPath, strlen('integration_settings.')));
            } elseif (str_starts_with($configPath, 'einvoice_settings.')) {
                $value = data_get($einvoiceSettings, substr($configPath, strlen('einvoice_settings.')));
            } else {
                $value = config($configPath);
            }

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
