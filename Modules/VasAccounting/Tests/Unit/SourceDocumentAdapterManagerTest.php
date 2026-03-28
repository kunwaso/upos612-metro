<?php

namespace Modules\VasAccounting\Tests\Unit;

use InvalidArgumentException;
use Modules\VasAccounting\Contracts\SourceDocumentAdapterInterface;
use Modules\VasAccounting\Services\SourceDocumentAdapterManager;
use Tests\TestCase;

class SourceDocumentAdapterManagerTest extends TestCase
{
    /**
     * @dataProvider sourceTypeProvider
     */
    public function test_it_resolves_every_configured_core_source_adapter(string $sourceType): void
    {
        $adapter = app(SourceDocumentAdapterManager::class)->resolve($sourceType);

        $this->assertInstanceOf(SourceDocumentAdapterInterface::class, $adapter);
    }

    public function test_it_throws_for_unknown_source_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SourceDocumentAdapterManager::class)->resolve('unknown-source');
    }

    public function sourceTypeProvider(): array
    {
        return [
            ['sell'],
            ['purchase'],
            ['expense'],
            ['transaction_payment'],
            ['sell_return'],
            ['purchase_return'],
            ['stock_adjustment'],
            ['stock_transfer'],
            ['opening_stock'],
        ];
    }
}
