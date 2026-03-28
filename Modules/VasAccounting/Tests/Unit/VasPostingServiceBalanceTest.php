<?php

namespace Modules\VasAccounting\Tests\Unit;

use Illuminate\Support\Collection;
use Mockery;
use Modules\VasAccounting\Services\SourceDocumentAdapterManager;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Tests\TestCase;

class VasPostingServiceBalanceTest extends TestCase
{
    public function test_assert_balanced_accepts_balanced_lines(): void
    {
        $service = $this->makeService();

        $service->assertBalancedPublic(collect([
            ['account_id' => 1, 'debit' => 100, 'credit' => 0],
            ['account_id' => 2, 'debit' => 0, 'credit' => 100],
        ]));

        $this->assertTrue(true);
    }

    public function test_assert_balanced_rejects_unbalanced_lines(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unbalanced VAS voucher payload');

        $service = $this->makeService();

        $service->assertBalancedPublic(collect([
            ['account_id' => 1, 'debit' => 120, 'credit' => 0],
            ['account_id' => 2, 'debit' => 0, 'credit' => 100],
        ]));
    }

    protected function makeService(): object
    {
        return new class(
            Mockery::mock(SourceDocumentAdapterManager::class),
            Mockery::mock(VasAccountingUtil::class),
            new LedgerPostingUtil()
        ) extends VasPostingService {
            public function assertBalancedPublic(Collection $lines): void
            {
                $this->assertBalanced($lines);
            }
        };
    }
}
