<?php

namespace Modules\VasAccounting\Tests\Unit;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\VasAccounting\Entities\VasVoucher;
use Modules\VasAccounting\Services\DocumentApprovalService;
use Modules\VasAccounting\Services\SourceDocumentAdapterManager;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use RuntimeException;
use Tests\TestCase;

class VasPostingServiceLifecycleTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_post_existing_voucher_returns_early_when_already_posted(): void
    {
        $ledgerPostingUtil = Mockery::mock(LedgerPostingUtil::class);
        $ledgerPostingUtil->shouldNotReceive('publishVoucher');

        $service = new VasPostingService(
            Mockery::mock(SourceDocumentAdapterManager::class),
            Mockery::mock(VasAccountingUtil::class),
            $ledgerPostingUtil,
            Mockery::mock(DocumentApprovalService::class)
        );

        $voucher = $this->fakeVoucher('posted', 'JV-00001');

        $result = $service->postExistingVoucher($voucher, 21);

        $this->assertSame($voucher, $result);
        $this->assertFalse($voucher->was_saved);
    }

    public function test_post_existing_voucher_promotes_draft_voucher_to_posted(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static function (callable $callback) {
                return $callback();
            });

        $ledgerPostingUtil = Mockery::mock(LedgerPostingUtil::class);
        $ledgerPostingUtil->shouldReceive('publishVoucher')
            ->once()
            ->with(Mockery::type(VasVoucher::class));
        $approvalService = Mockery::mock(DocumentApprovalService::class);
        $approvalService->shouldReceive('canPostVoucher')->once()->with(Mockery::type(VasVoucher::class))->andReturnTrue();

        $service = new VasPostingService(
            Mockery::mock(SourceDocumentAdapterManager::class),
            Mockery::mock(VasAccountingUtil::class),
            $ledgerPostingUtil,
            $approvalService
        );

        $voucher = $this->fakeVoucher('draft', 'JV-00002');

        $result = $service->postExistingVoucher($voucher, 21);

        $this->assertSame('posted', $result->status);
        $this->assertSame(21, $result->posted_by);
        $this->assertSame(21, $result->submitted_by);
        $this->assertSame(21, $result->approved_by);
        $this->assertInstanceOf(Carbon::class, $result->posted_at);
        $this->assertTrue($result->was_saved);
    }

    public function test_reverse_voucher_refuses_to_reverse_unposted_documents(): void
    {
        $service = new VasPostingService(
            Mockery::mock(SourceDocumentAdapterManager::class),
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(LedgerPostingUtil::class),
            Mockery::mock(DocumentApprovalService::class)
        );

        $voucher = $this->fakeVoucher('draft', 'JV-00003');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only posted vouchers can be reversed');

        $service->reverseVoucher($voucher, 21);
    }

    public function test_reverse_voucher_routes_posted_documents_through_reversal_creation(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static function (callable $callback) {
                return $callback();
            });

        $service = Mockery::mock(VasPostingService::class, [
            Mockery::mock(SourceDocumentAdapterManager::class),
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(LedgerPostingUtil::class),
            Mockery::mock(DocumentApprovalService::class),
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $voucher = $this->fakeVoucher('posted', 'JV-00004');
        $reversal = $this->fakeVoucher('posted', 'RV-00004');

        $service->shouldReceive('createReversalVoucher')
            ->once()
            ->with($voucher, 21)
            ->andReturn($reversal);

        $result = $service->reverseVoucher($voucher, 21);

        $this->assertSame($reversal, $result);
    }

    public function test_post_existing_voucher_rejects_when_approval_is_still_required(): void
    {
        $approvalService = Mockery::mock(DocumentApprovalService::class);
        $approvalService->shouldReceive('canPostVoucher')->once()->andReturnFalse();

        $service = new VasPostingService(
            Mockery::mock(SourceDocumentAdapterManager::class),
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(LedgerPostingUtil::class),
            $approvalService
        );

        $voucher = $this->fakeVoucher('draft', 'JV-00005');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must be approved before posting');

        $service->postExistingVoucher($voucher, 21);
    }

    protected function fakeVoucher(string $status, string $voucherNo): VasVoucher
    {
        return new class($status, $voucherNo) extends VasVoucher {
            public bool $was_saved = false;

            public function __construct(string $status, string $voucherNo)
            {
                parent::__construct();
                $this->status = $status;
                $this->voucher_no = $voucherNo;
            }

            public function fresh($with = [])
            {
                return $this;
            }

            public function save(array $options = [])
            {
                $this->was_saved = true;

                return true;
            }
        };
    }
}
