<?php

namespace Modules\VasAccounting\Tests\Unit;

use App\User;
use Modules\VasAccounting\Http\Requests\EInvoiceActionRequest;
use Modules\VasAccounting\Http\Requests\ExportTaxRequest;
use Modules\VasAccounting\Http\Requests\StoreSetupRequest;
use Tests\TestCase;

class VasAccountingRequestAuthorizationTest extends TestCase
{
    public function test_store_setup_request_allows_compliance_admin_permission(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.compliance.admin']));

        $request = StoreSetupRequest::create('/vas-accounting/setup', 'POST');

        $this->assertTrue($request->authorize());
    }

    public function test_export_tax_request_allows_filing_operator_permission(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.filing.operator']));

        $request = ExportTaxRequest::create('/vas-accounting/tax/export', 'POST', [
            'export_type' => 'vat_declaration',
        ]);

        $this->assertTrue($request->authorize());
    }

    public function test_einvoice_action_request_allows_filing_operator_permission(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.filing.operator']));

        $request = EInvoiceActionRequest::create('/vas-accounting/e-invoices/1/issue', 'POST');

        $this->assertTrue($request->authorize());
    }

    protected function makeUser(array $allowedAbilities): User
    {
        return new class($allowedAbilities) extends User
        {
            protected array $allowedAbilities = [];

            public function __construct(array $allowedAbilities)
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 44;
                $this->allowedAbilities = $allowedAbilities;
            }

            public function hasRole($roles, ?string $guard = null): bool
            {
                return false;
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return in_array((string) $permission, $this->allowedAbilities, true);
            }

            public function checkPermissionTo($permission, $guardName = null): bool
            {
                return $this->hasPermissionTo($permission, $guardName);
            }

            public function can($ability, $arguments = [])
            {
                return in_array((string) $ability, $this->allowedAbilities, true);
            }
        };
    }
}
