<?php

namespace Modules\VasAccounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\VasAccounting\Services\ComplianceProfileService;
use RuntimeException;

class StoreSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return auth()->user()->can('vas_accounting.setup.manage')
            || auth()->user()->can('vas_accounting.compliance.admin');
    }

    public function rules(): array
    {
        $documentStatuses = array_keys((array) config('vasaccounting.document_statuses', []));
        $einvoiceProviders = array_keys((array) config('vasaccounting.einvoice_adapters', []));
        $bankProviders = array_keys((array) config('vasaccounting.bank_statement_import_adapters', []));
        $taxProviders = array_keys((array) config('vasaccounting.tax_export_adapters', []));
        $payrollProviders = array_keys((array) config('vasaccounting.payroll_bridge_adapters', []));
        $complianceProfiles = array_keys((array) config('vasaccounting.compliance_profiles.profiles', []));

        return [
            'book_currency' => ['required', 'string', 'max:10'],
            'inventory_method' => ['required', 'string', 'max:50'],
            'is_enabled' => ['nullable', 'boolean'],
            'posting_map' => ['nullable', 'array'],
            'posting_map.*' => ['nullable', 'integer'],
            'compliance_settings' => ['nullable', 'array'],
            'compliance_settings.standard' => ['required', 'string', Rule::in($complianceProfiles)],
            'compliance_settings.effective_date' => ['required', 'date'],
            'compliance_settings.legacy_bridge_enabled' => ['nullable', 'boolean'],
            'compliance_settings.profile_version' => ['nullable', 'string', 'max:20'],
            'einvoice_settings.provider' => ['nullable', 'string', Rule::in($einvoiceProviders)],
            'einvoice_settings.mode' => ['nullable', 'string', 'max:20'],
            'einvoice_settings.issue_on_post' => ['nullable', 'boolean'],
            'depreciation_settings.method' => ['nullable', 'string', 'max:50'],
            'depreciation_settings.run_day_of_month' => ['nullable', 'integer', 'between:1,28'],
            'tax_settings.declaration_currency' => ['nullable', 'string', 'max:10'],
            'feature_flags' => ['nullable', 'array'],
            'feature_flags.*' => ['nullable', 'boolean'],
            'approval_settings.default_manual_voucher_status' => ['nullable', 'string', Rule::in($documentStatuses)],
            'approval_settings.require_manual_voucher_approval' => ['nullable', 'boolean'],
            'integration_settings.api_guard' => ['nullable', 'string', 'max:50'],
            'integration_settings.bank_statement_provider' => ['nullable', 'string', Rule::in($bankProviders)],
            'integration_settings.tax_export_provider' => ['nullable', 'string', Rule::in($taxProviders)],
            'integration_settings.payroll_bridge_provider' => ['nullable', 'string', Rule::in($payrollProviders)],
            'integration_settings.vnpt_api_base_url' => ['nullable', 'string', 'max:255'],
            'integration_settings.vnpt_client_id' => ['nullable', 'string', 'max:255'],
            'integration_settings.vnpt_client_secret' => ['nullable', 'string', 'max:255'],
            'integration_settings.vnpt_tax_username' => ['nullable', 'string', 'max:255'],
            'integration_settings.vnpt_tax_password' => ['nullable', 'string', 'max:255'],
            'ui_settings' => ['nullable', 'array'],
            'ui_settings.locale' => ['nullable', 'string', Rule::in(array_keys((array) config('constants.langs', [])))],
            'ui_settings.navigation_mode' => ['nullable', 'string', Rule::in(['advanced', 'basic'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $payload = $this->all();

            try {
                app(ComplianceProfileService::class)->validateSetupPayload($payload);
            } catch (RuntimeException $exception) {
                $validator->errors()->add('compliance_settings.standard', $exception->getMessage());
            }

            $integrationSettings = (array) ($payload['integration_settings'] ?? []);
            $einvoiceProvider = (string) data_get($payload, 'einvoice_settings.provider', 'sandbox');
            $taxProvider = (string) ($integrationSettings['tax_export_provider'] ?? 'local');
            $requiredVnptKeys = [
                'vnpt_api_base_url',
                'vnpt_client_id',
                'vnpt_client_secret',
            ];

            if ($einvoiceProvider === 'vnpt' || $taxProvider === 'vnpt') {
                foreach ($requiredVnptKeys as $field) {
                    if (trim((string) ($integrationSettings[$field] ?? '')) === '') {
                        $validator->errors()->add('integration_settings.' . $field, 'This field is required for VNPT provider setup.');
                    }
                }
            }

            if ($taxProvider === 'vnpt') {
                foreach (['vnpt_tax_username', 'vnpt_tax_password'] as $field) {
                    if (trim((string) ($integrationSettings[$field] ?? '')) === '') {
                        $validator->errors()->add('integration_settings.' . $field, 'This field is required for VNPT tax filing.');
                    }
                }
            }
        });
    }
}
