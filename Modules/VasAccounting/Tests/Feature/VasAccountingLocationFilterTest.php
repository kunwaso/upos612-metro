<?php

namespace Modules\VasAccounting\Tests\Feature;

use App\User;
use Tests\TestCase;

class VasAccountingLocationFilterTest extends TestCase
{
    public function test_header_renders_location_filter_only_for_location_aware_pages(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.access']));

        $locationAwareHtml = view('vasaccounting::partials.header', [
            'title' => 'Cash & Bank',
            'subtitle' => 'Location-aware operations',
            'vasAccountingPageMeta' => [
                'title' => 'Cash & Bank',
                'subtitle' => 'Location-aware operations',
                'section_label' => 'Operations',
                'badge_variant' => 'light-success',
                'supports_location_filter' => true,
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
            'locationOptions' => [9 => 'Branch 9'],
            'selectedLocationId' => 9,
        ])->render();

        $businessWideHtml = view('vasaccounting::partials.header', [
            'title' => 'Reports',
            'subtitle' => 'Business-wide reporting',
            'vasAccountingPageMeta' => [
                'title' => 'Reports',
                'subtitle' => 'Business-wide reporting',
                'section_label' => 'Controls',
                'badge_variant' => 'light-warning',
                'supports_location_filter' => false,
                'quick_actions' => [],
            ],
            'vasAccountingBusinessContext' => ['label' => 'Demo Business'],
            'vasAccountingCurrentPeriod' => null,
            'vasAccountingNavConfig' => ['navigation_groups' => []],
            'locationOptions' => [9 => 'Branch 9'],
            'selectedLocationId' => 9,
        ])->render();

        $this->assertStringContainsString('name="location_id"', $locationAwareHtml);
        $this->assertStringContainsString('Branch 9', $locationAwareHtml);
        $this->assertStringNotContainsString('name="location_id"', $businessWideHtml);
    }

    public function test_existing_business_location_form_fields_remain_intact(): void
    {
        $paths = [
            module_path('VasAccounting', 'Resources/views/cash_bank/index.blade.php'),
            module_path('VasAccounting', 'Resources/views/inventory/index.blade.php'),
            module_path('VasAccounting', 'Resources/views/tools/index.blade.php'),
            module_path('VasAccounting', 'Resources/views/fixed_assets/index.blade.php'),
            module_path('VasAccounting', 'Resources/views/contracts/index.blade.php'),
            module_path('VasAccounting', 'Resources/views/costing/index.blade.php'),
        ];

        foreach ($paths as $path) {
            $contents = file_get_contents($path);
            $this->assertIsString($contents);
            $this->assertStringContainsString('business_location_id', $contents, $path);
        }
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
