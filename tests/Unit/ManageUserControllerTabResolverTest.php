<?php

namespace Tests\Unit;

use App\Http\Controllers\ManageUserController;
use App\Utils\ModuleUtil;
use App\Utils\TwoFactorUtil;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ManageUserControllerTabResolverTest extends TestCase
{
    /**
     * @dataProvider allowedTabsProvider
     */
    public function test_it_returns_allowed_tabs_without_changes($tab): void
    {
        $this->assertSame($tab, $this->resolveTab($tab));
    }

    /**
     * @dataProvider invalidTabsProvider
     */
    public function test_it_falls_back_to_overview_for_invalid_tabs($tab): void
    {
        $this->assertSame('overview', $this->resolveTab($tab));
    }

    public static function allowedTabsProvider(): array
    {
        return [
            ['overview'],
            ['settings'],
            ['documents'],
            ['activities'],
        ];
    }

    public static function invalidTabsProvider(): array
    {
        return [
            [null],
            [''],
            ['unknown'],
            ['SETTINGS'],
            ['billing'],
        ];
    }

    private function resolveTab($tab): string
    {
        $controller = new ManageUserController(
            $this->createMock(ModuleUtil::class),
            $this->createMock(TwoFactorUtil::class)
        );
        $method = new ReflectionMethod($controller, 'resolveUserAccountTab');
        $method->setAccessible(true);

        return $method->invoke($controller, $tab);
    }
}
