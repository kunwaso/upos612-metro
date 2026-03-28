<?php

namespace Modules\VasAccounting\Tests\Unit;

use InvalidArgumentException;
use Modules\VasAccounting\Contracts\EInvoiceAdapterInterface;
use Modules\VasAccounting\Services\EInvoiceAdapterManager;
use Tests\TestCase;

class EInvoiceAdapterManagerTest extends TestCase
{
    public function test_it_resolves_the_sandbox_adapter(): void
    {
        $adapter = app(EInvoiceAdapterManager::class)->resolve('sandbox');

        $this->assertInstanceOf(EInvoiceAdapterInterface::class, $adapter);
    }

    public function test_it_throws_for_unknown_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(EInvoiceAdapterManager::class)->resolve('missing-provider');
    }
}
