<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminSidebarMenu;
use App\Http\Middleware\CheckUserLogin;
use App\Http\Middleware\Language;
use App\Http\Middleware\SetSessionData;
use App\Http\Middleware\Timezone;
use App\Http\Middleware\VerifyCsrfToken;
use App\User;
use Tests\TestCase;

class ProductDetailStockAdjustAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            SetSessionData::class,
            Language::class,
            Timezone::class,
            AdminSidebarMenu::class,
            CheckUserLogin::class,
            VerifyCsrfToken::class,
        ]);
    }

    public function test_adjust_detail_stock_returns_403_for_non_org_admin_even_with_permissions(): void
    {
        $this->actingAs($this->makeUser(false, [
            'product.update' => true,
            'product.opening_stock' => true,
            'stock_adjustment.create' => true,
        ]));

        $response = $this->withSession([
            'user.business_id' => 44,
            'user.id' => 1,
        ])->postJson('/products/detail/10/stock-adjust', [
            'location_id' => 1,
            'variation_id' => 1,
            'target_stock' => 10,
            'reason' => 'Fix wrong input',
        ]);

        $response->assertStatus(403);
    }

    public function test_adjust_detail_stock_reaches_validation_for_org_admin_with_permissions(): void
    {
        $this->actingAs($this->makeUser(true, [
            'product.update' => true,
            'product.opening_stock' => true,
        ]));

        $response = $this->withSession([
            'user.business_id' => 44,
            'user.id' => 1,
        ])->postJson('/products/detail/10/stock-adjust', []);

        $response->assertStatus(422);
    }

    public function test_stock_detail_partial_hides_direct_stock_modal_for_non_org_admin(): void
    {
        $this->actingAs($this->makeUser(false, [
            'product.update' => true,
            'product.opening_stock' => true,
            'stock_adjustment.create' => true,
        ]));

        $html = view('product.partials.product_stock_details', [
            'product_stock_details' => collect(),
            'is_org_admin' => false,
        ])->render();

        $this->assertStringNotContainsString('id="directStockEditModal"', $html);
    }

    public function test_stock_detail_partial_shows_direct_stock_modal_for_org_admin_with_permissions(): void
    {
        $this->actingAs($this->makeUser(true, [
            'product.update' => true,
            'product.opening_stock' => true,
        ]));

        $html = view('product.partials.product_stock_details', [
            'product_stock_details' => collect(),
            'is_org_admin' => true,
        ])->render();

        $this->assertStringContainsString('id="directStockEditModal"', $html);
    }

    protected function makeUser(bool $isOrgAdmin, array $abilities): User
    {
        return new class($isOrgAdmin, $abilities) extends User
        {
            protected bool $isOrgAdmin = false;

            protected array $abilities = [];

            public function __construct(bool $isOrgAdmin = false, array $abilities = [])
            {
                parent::__construct();
                $this->id = 1;
                $this->business_id = 44;
                $this->isOrgAdmin = $isOrgAdmin;
                $this->abilities = $abilities;
            }

            public function hasRole($roles, ?string $guard = null): bool
            {
                if (! $this->isOrgAdmin) {
                    return false;
                }

                if (is_array($roles)) {
                    foreach ($roles as $role) {
                        if ($this->isAdminRole($role)) {
                            return true;
                        }
                    }

                    return false;
                }

                return $this->isAdminRole($roles);
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return $this->can($permission);
            }

            public function checkPermissionTo($permission, $guardName = null): bool
            {
                return $this->can($permission);
            }

            public function can($ability, $arguments = [])
            {
                return (bool) ($this->abilities[$ability] ?? false);
            }

            protected function isAdminRole($role): bool
            {
                return (string) $role === 'Admin#44' || str_starts_with((string) $role, 'Admin#');
            }
        };
    }
}
