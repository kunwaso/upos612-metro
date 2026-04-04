<?php

namespace Modules\VasAccounting\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\VasAccounting\Contracts\SourceDocumentAdapterInterface;
use Modules\VasAccounting\Services\DocumentApprovalService;
use Modules\VasAccounting\Services\SourceDocumentAdapterManager;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class VasPostingServiceDeleteCleanupTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createTables();
    }

    public function test_deleted_purchase_cleanup_removes_source_and_reversal_vouchers_without_posting_a_new_source_voucher(): void
    {
        DB::table('vas_accounting_periods')->insert([
            'id' => 1,
            'business_id' => 44,
            'name' => 'Apr 2026',
            'status' => 'open',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vas_vouchers')->insert([
            [
                'id' => 1001,
                'business_id' => 44,
                'accounting_period_id' => 1,
                'voucher_no' => 'PV-00001',
                'voucher_type' => 'purchase_invoice',
                'sequence_key' => 'purchase_invoice',
                'source_type' => 'purchase',
                'source_id' => 3,
                'status' => 'reversed',
                'version_no' => 1,
                'is_system_generated' => 1,
                'is_reversal' => 0,
                'reversed_voucher_id' => 1002,
                'posted_at' => now(),
                'posted_by' => 7,
                'reversed_at' => now(),
                'reversed_by' => 7,
                'created_by' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1002,
                'business_id' => 44,
                'accounting_period_id' => 1,
                'voucher_no' => 'JV-00004',
                'voucher_type' => 'reversal',
                'sequence_key' => 'general_journal',
                'source_type' => 'purchase_reversal',
                'source_id' => 4001,
                'status' => 'posted',
                'version_no' => 1,
                'is_system_generated' => 1,
                'is_reversal' => 1,
                'reversed_voucher_id' => 1001,
                'posted_at' => now(),
                'posted_by' => 7,
                'reversed_at' => null,
                'reversed_by' => null,
                'created_by' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 1003,
                'business_id' => 44,
                'accounting_period_id' => 1,
                'voucher_no' => 'PV-00002',
                'voucher_type' => 'purchase_invoice_reversal',
                'sequence_key' => 'purchase_invoice',
                'source_type' => 'purchase',
                'source_id' => 3,
                'status' => 'posted',
                'version_no' => 2,
                'is_system_generated' => 1,
                'is_reversal' => 0,
                'reversed_voucher_id' => null,
                'posted_at' => now(),
                'posted_by' => 7,
                'reversed_at' => null,
                'reversed_by' => null,
                'created_by' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('vas_voucher_lines')->insert([
            ['business_id' => 44, 'voucher_id' => 1001, 'line_no' => 1, 'account_id' => 331, 'debit' => 0, 'credit' => 24750000, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'voucher_id' => 1001, 'line_no' => 2, 'account_id' => 156, 'debit' => 24750000, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'voucher_id' => 1002, 'line_no' => 1, 'account_id' => 331, 'debit' => 24750000, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'voucher_id' => 1002, 'line_no' => 2, 'account_id' => 156, 'debit' => 0, 'credit' => 24750000, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'voucher_id' => 1003, 'line_no' => 1, 'account_id' => 331, 'debit' => 24750000, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'voucher_id' => 1003, 'line_no' => 2, 'account_id' => 156, 'debit' => 0, 'credit' => 24750000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('vas_journal_entries')->insert([
            ['business_id' => 44, 'accounting_period_id' => 1, 'voucher_id' => 1001, 'voucher_line_id' => 1, 'account_id' => 331, 'posting_date' => '2026-04-02', 'debit' => 0, 'credit' => 24750000, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'accounting_period_id' => 1, 'voucher_id' => 1001, 'voucher_line_id' => 2, 'account_id' => 156, 'posting_date' => '2026-04-02', 'debit' => 24750000, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'accounting_period_id' => 1, 'voucher_id' => 1002, 'voucher_line_id' => 3, 'account_id' => 331, 'posting_date' => '2026-04-02', 'debit' => 24750000, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'accounting_period_id' => 1, 'voucher_id' => 1002, 'voucher_line_id' => 4, 'account_id' => 156, 'posting_date' => '2026-04-02', 'debit' => 0, 'credit' => 24750000, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'accounting_period_id' => 1, 'voucher_id' => 1003, 'voucher_line_id' => 5, 'account_id' => 331, 'posting_date' => '2026-04-02', 'debit' => 24750000, 'credit' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['business_id' => 44, 'accounting_period_id' => 1, 'voucher_id' => 1003, 'voucher_line_id' => 6, 'account_id' => 156, 'posting_date' => '2026-04-02', 'debit' => 0, 'credit' => 24750000, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('vas_posting_failures')->insert([
            'business_id' => 44,
            'source_type' => 'purchase',
            'source_id' => 3,
            'payload' => json_encode(['is_deleted' => true]),
            'failed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adapter = Mockery::mock(SourceDocumentAdapterInterface::class);
        $adapter->shouldReceive('loadSourceDocument')
            ->once()
            ->with(3, Mockery::type('array'))
            ->andReturn((object) [
                'id' => 3,
                'business_id' => 44,
                'created_by' => 7,
            ]);
        $adapter->shouldNotReceive('toVoucherPayload');

        $adapterManager = Mockery::mock(SourceDocumentAdapterManager::class);
        $adapterManager->shouldReceive('resolve')->once()->with('purchase')->andReturn($adapter);

        $service = new VasPostingService(
            $adapterManager,
            Mockery::mock(VasAccountingUtil::class),
            new LedgerPostingUtil(),
            Mockery::mock(DocumentApprovalService::class)
        );

        $service->processSourceDocument('purchase', 3, [
            'is_deleted' => true,
            'source_snapshot' => [
                'id' => 3,
                'business_id' => 44,
                'created_by' => 7,
            ],
        ]);

        $this->assertDatabaseCount('vas_vouchers', 0);
        $this->assertDatabaseCount('vas_voucher_lines', 0);
        $this->assertDatabaseCount('vas_journal_entries', 0);
        $this->assertDatabaseHas('vas_ledger_balances', [
            'business_id' => 44,
            'accounting_period_id' => 1,
            'account_id' => 331,
            'period_debit' => 0,
            'period_credit' => 0,
        ]);
        $this->assertDatabaseHas('vas_ledger_balances', [
            'business_id' => 44,
            'accounting_period_id' => 1,
            'account_id' => 156,
            'period_debit' => 0,
            'period_credit' => 0,
        ]);
        $this->assertDatabaseHas('vas_posting_failures', [
            'business_id' => 44,
            'source_type' => 'purchase',
            'source_id' => 3,
        ]);
        $this->assertNotNull(DB::table('vas_posting_failures')->value('resolved_at'));
    }

    protected function createTables(): void
    {
        Schema::create('vas_accounting_periods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->default('open');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('vas_vouchers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('accounting_period_id')->nullable();
            $table->string('voucher_no')->nullable();
            $table->string('voucher_type')->nullable();
            $table->string('sequence_key')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('version_no')->default(1);
            $table->boolean('is_system_generated')->default(false);
            $table->boolean('is_reversal')->default(false);
            $table->unsignedInteger('reversed_voucher_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('vas_voucher_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('voucher_id');
            $table->unsignedInteger('line_no')->default(1);
            $table->unsignedInteger('account_id')->nullable();
            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('vas_journal_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('accounting_period_id')->nullable();
            $table->unsignedInteger('voucher_id');
            $table->unsignedInteger('voucher_line_id');
            $table->unsignedInteger('account_id')->nullable();
            $table->date('posting_date')->nullable();
            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('vas_ledger_balances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('accounting_period_id');
            $table->unsignedInteger('account_id');
            $table->decimal('opening_debit', 22, 4)->default(0);
            $table->decimal('opening_credit', 22, 4)->default(0);
            $table->decimal('period_debit', 22, 4)->default(0);
            $table->decimal('period_credit', 22, 4)->default(0);
            $table->decimal('closing_debit', 22, 4)->default(0);
            $table->decimal('closing_credit', 22, 4)->default(0);
            $table->timestamp('last_posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vas_document_approvals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('entity_type')->nullable();
            $table->unsignedInteger('entity_id')->nullable();
            $table->timestamps();
        });

        Schema::create('vas_document_audit_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('entity_type')->nullable();
            $table->unsignedInteger('entity_id')->nullable();
            $table->timestamps();
        });

        Schema::create('vas_document_attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('entity_type')->nullable();
            $table->unsignedInteger('entity_id')->nullable();
            $table->timestamps();
        });

        Schema::create('vas_posting_failures', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('source_type');
            $table->unsignedInteger('source_id');
            $table->text('payload')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('resolved_by')->nullable();
            $table->timestamps();
        });
    }
}
