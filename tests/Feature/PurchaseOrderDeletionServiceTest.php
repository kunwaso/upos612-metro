<?php

namespace Tests\Feature;

use App\Events\PurchaseCreatedOrModified;
use App\Services\PurchaseOrderDeletionService;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Services\PutawayService;
use Modules\StorageManager\Services\ReceivingService;
use Modules\StorageManager\Utils\StorageVasReceiptSyncUtil;
use Modules\VasAccounting\Services\VasPostingService;
use RuntimeException;
use Tests\TestCase;

class PurchaseOrderDeletionServiceTest extends TestCase
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
        Schema::dropAllTables();

        $this->createTables();
    }

    public function test_delete_purchase_order_cascade_removes_generated_chain(): void
    {
        Event::fake();

        DB::table('transactions')->insert([
            [
                'id' => 10,
                'business_id' => 44,
                'location_id' => 3,
                'type' => 'purchase_order',
                'status' => 'partial',
                'payment_status' => null,
                'ref_no' => 'PO-0010',
                'invoice_no' => null,
                'source' => null,
                'transaction_date' => '2026-04-04',
                'return_parent_id' => null,
                'created_by' => 7,
                'final_total' => 0,
                'tax_amount' => 0,
                'purchase_order_ids' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'business_id' => 44,
                'location_id' => 3,
                'type' => 'purchase',
                'status' => 'pending',
                'payment_status' => 'due',
                'invoice_no' => null,
                'ref_no' => 'PUR-0020',
                'source' => ReceivingService::GENERATED_PURCHASE_SOURCE,
                'transaction_date' => '2026-04-04',
                'return_parent_id' => null,
                'created_by' => 7,
                'final_total' => 0,
                'tax_amount' => 0,
                'purchase_order_ids' => json_encode([10]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 30,
                'business_id' => 44,
                'location_id' => 3,
                'type' => 'purchase_return',
                'status' => 'final',
                'payment_status' => null,
                'ref_no' => 'PR-0030',
                'invoice_no' => null,
                'source' => null,
                'transaction_date' => '2026-04-04',
                'return_parent_id' => 20,
                'created_by' => 7,
                'final_total' => 0,
                'tax_amount' => 0,
                'purchase_order_ids' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('purchase_lines')->insert([
            [
                'id' => 101,
                'transaction_id' => 10,
                'product_id' => 5,
                'variation_id' => 9,
                'quantity' => 5,
                'po_quantity_purchased' => 5,
                'purchase_price' => 10,
                'purchase_price_inc_tax' => 10,
                'item_tax' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 201,
                'transaction_id' => 20,
                'product_id' => 5,
                'variation_id' => 9,
                'quantity' => 5,
                'purchase_order_line_id' => 101,
                'purchase_price' => 10,
                'purchase_price_inc_tax' => 10,
                'item_tax' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('account_transactions')->insert([
            ['transaction_id' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['transaction_id' => 30, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('storage_documents')->insert([
            'id' => 501,
            'business_id' => 44,
            'location_id' => 3,
            'parent_document_id' => null,
            'document_type' => 'receipt',
            'source_type' => 'purchase',
            'source_id' => 20,
            'status' => 'completed',
            'workflow_state' => 'putaway_pending',
            'sync_status' => 'posted',
            'vas_inventory_document_id' => 9001,
            'document_no' => 'RCV-0501',
            'meta' => json_encode([
                'storage_manager' => [
                    'generated_from_purchase_order' => true,
                    'purchase_order_id' => 10,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('storage_documents')->insert([
            'id' => 502,
            'business_id' => 44,
            'location_id' => 3,
            'parent_document_id' => null,
            'document_type' => 'receipt',
            'source_type' => 'purchase_order',
            'source_id' => 10,
            'status' => 'draft',
            'workflow_state' => 'expected',
            'sync_status' => 'not_required',
            'vas_inventory_document_id' => null,
            'document_no' => 'RCV-0502',
            'meta' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('storage_documents')->insert([
            'id' => 601,
            'business_id' => 44,
            'location_id' => 3,
            'parent_document_id' => 501,
            'document_type' => 'putaway',
            'source_type' => 'purchase',
            'source_id' => 20,
            'status' => 'completed',
            'workflow_state' => 'closed',
            'sync_status' => 'posted',
            'vas_inventory_document_id' => 9002,
            'document_no' => 'PUT-0601',
            'meta' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('storage_document_lines')->insert([
            ['id' => 701, 'business_id' => 44, 'document_id' => 501, 'line_no' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 702, 'business_id' => 44, 'document_id' => 601, 'line_no' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('storage_tasks')->insert([
            ['id' => 801, 'business_id' => 44, 'location_id' => 3, 'document_id' => 601, 'document_line_id' => 702, 'task_type' => 'putaway', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('storage_task_events')->insert([
            ['id' => 901, 'business_id' => 44, 'task_id' => 801, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('storage_document_links')->insert([
            ['id' => 1001, 'business_id' => 44, 'document_id' => 501, 'linked_system' => 'vas', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 1002, 'business_id' => 44, 'document_id' => 601, 'linked_system' => 'vas', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('storage_sync_logs')->insert([
            ['id' => 1101, 'business_id' => 44, 'document_id' => 501, 'linked_system' => 'vas', 'action' => 'sync', 'status' => 'posted', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 1102, 'business_id' => 44, 'document_id' => 601, 'linked_system' => 'vas', 'action' => 'sync', 'status' => 'posted', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('storage_inventory_movements')->insert([
            'id' => 1201,
            'business_id' => 44,
            'document_id' => 501,
            'document_line_id' => 701,
            'task_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('storage_inventory_movements')->insert([
            'id' => 1202,
            'business_id' => 44,
            'document_id' => 601,
            'document_line_id' => 702,
            'task_id' => 801,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('storage_approval_requests')->insert([
            ['id' => 1301, 'business_id' => 44, 'document_id' => 601, 'document_line_id' => 702, 'created_at' => now(), 'updated_at' => now()],
        ]);
        Schema::create('vas_vouchers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        $productUtil = Mockery::mock(ProductUtil::class);
        $productUtil->shouldReceive('updatePurchaseOrderLine')->zeroOrMoreTimes();
        $productUtil->shouldReceive('updateProductQuantity')->zeroOrMoreTimes();
        $productUtil->shouldReceive('decreaseProductQuantity')->zeroOrMoreTimes();

        $transactionUtil = Mockery::mock(TransactionUtil::class);
        $transactionUtil->shouldReceive('activityLog')->twice();
        $transactionUtil->shouldReceive('adjustMappingPurchaseSellAfterEditingPurchase')->zeroOrMoreTimes();
        $transactionUtil->shouldReceive('updatePurchaseOrderStatus')->once()->with([10]);

        $receivingService = Mockery::mock(ReceivingService::class);
        $receivingService->shouldReceive('reopenGeneratedPurchaseOrderReceiptForDeletion')
            ->once()
            ->andReturnUsing(function (int $businessId, StorageDocument $document, int $userId) {
                $document->status = 'open';
                $document->sync_status = 'not_required';
                $document->save();

                return $document->fresh();
            });
        $receivingService->shouldReceive('reopenReceipt')->zeroOrMoreTimes();

        $putawayService = Mockery::mock(PutawayService::class);
        $putawayService->shouldReceive('reopenPutaway')
            ->once()
            ->andReturnUsing(function (int $businessId, StorageDocument $document, int $userId) {
                $document->status = 'open';
                $document->sync_status = 'not_required';
                $document->save();

                return $document->fresh();
            });

        $storageVasSyncUtil = Mockery::mock(StorageVasReceiptSyncUtil::class);
        $storageVasSyncUtil->shouldReceive('unlinkDocumentVasSync')
            ->twice()
            ->andReturnUsing(function (int $businessId, StorageDocument $document, int $userId) {
                $document->sync_status = 'not_required';
                $document->vas_inventory_document_id = null;
                $document->save();

                return ['action_taken' => 'reversed'];
            });

        $vasPostingService = Mockery::mock(VasPostingService::class);
        $vasPostingService->shouldReceive('queueSourceDocument')
            ->once()
            ->withArgs(function (string $sourceType, $sourceDocument, array $context) {
                return $sourceType === 'purchase_return'
                    && (int) $sourceDocument->id === 30
                    && ! empty($context['is_deleted'])
                    && (int) ($context['source_snapshot']['id'] ?? 0) === 30;
            });

        $service = new PurchaseOrderDeletionService(
            $productUtil,
            $transactionUtil,
            $receivingService,
            $putawayService,
            $storageVasSyncUtil,
            $vasPostingService
        );

        $result = $service->delete(44, 10, 7);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['summary']['generated_purchases_deleted']);
        $this->assertSame(1, $result['summary']['purchase_returns_deleted']);
        $this->assertSame(0, $result['summary']['payments_deleted']);
        $this->assertSame(3, $result['summary']['storage_documents_deleted']);
        $this->assertSame(1, $result['summary']['putaways_reopened']);
        $this->assertSame(1, $result['summary']['receipts_reopened']);
        $this->assertSame(2, $result['summary']['vas_links_removed']);

        $this->assertDatabaseMissing('transactions', ['id' => 10]);
        $this->assertDatabaseMissing('transactions', ['id' => 20]);
        $this->assertDatabaseMissing('transactions', ['id' => 30]);
        $this->assertDatabaseCount('transaction_payments', 0);
        $this->assertDatabaseCount('purchase_lines', 0);
        $this->assertDatabaseCount('storage_documents', 0);
        $this->assertDatabaseCount('storage_document_lines', 0);
        $this->assertDatabaseCount('storage_tasks', 0);
        $this->assertDatabaseCount('storage_task_events', 0);
        $this->assertDatabaseCount('storage_document_links', 0);
        $this->assertDatabaseCount('storage_sync_logs', 0);
        $this->assertDatabaseCount('storage_inventory_movements', 0);
        $this->assertDatabaseCount('storage_approval_requests', 0);
        $this->assertDatabaseCount('account_transactions', 2);
        $this->assertSoftDeleted('account_transactions', ['transaction_id' => 20]);
        $this->assertSoftDeleted('account_transactions', ['transaction_id' => 30]);

        Event::assertDispatched(PurchaseCreatedOrModified::class, function (PurchaseCreatedOrModified $event) {
            return (int) $event->transaction->id === 20 && $event->isDeleted === true;
        });
    }

    public function test_delete_purchase_order_rejects_non_generated_linked_purchase(): void
    {
        $this->seedMinimalPurchaseOrder();

        DB::table('transactions')->insert([
            'id' => 40,
            'business_id' => 44,
            'location_id' => 3,
            'type' => 'purchase',
            'status' => 'pending',
            'payment_status' => null,
            'ref_no' => 'PUR-0040',
            'invoice_no' => null,
            'source' => null,
            'transaction_date' => '2026-04-04',
            'return_parent_id' => null,
            'created_by' => 7,
            'final_total' => 0,
            'tax_amount' => 0,
            'purchase_order_ids' => json_encode([10]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productUtil = Mockery::mock(ProductUtil::class);
        $transactionUtil = Mockery::mock(TransactionUtil::class);
        $receivingService = Mockery::mock(ReceivingService::class);
        $putawayService = Mockery::mock(PutawayService::class);
        $storageVasSyncUtil = Mockery::mock(StorageVasReceiptSyncUtil::class);
        $vasPostingService = Mockery::mock(VasPostingService::class);

        $service = new PurchaseOrderDeletionService(
            $productUtil,
            $transactionUtil,
            $receivingService,
            $putawayService,
            $storageVasSyncUtil,
            $vasPostingService
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('were not generated by StorageManager');

        $service->delete(44, 10, 7);
    }

    protected function seedMinimalPurchaseOrder(): void
    {
        DB::table('transactions')->insert([
            'id' => 10,
            'business_id' => 44,
            'location_id' => 3,
            'type' => 'purchase_order',
            'status' => 'ordered',
            'payment_status' => null,
            'ref_no' => 'PO-0010',
            'invoice_no' => null,
            'source' => null,
            'transaction_date' => '2026-04-04',
            'return_parent_id' => null,
            'created_by' => 7,
            'final_total' => 0,
            'tax_amount' => 0,
            'purchase_order_ids' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('purchase_lines')->insert([
            'id' => 101,
            'transaction_id' => 10,
            'product_id' => 5,
            'variation_id' => 9,
            'quantity' => 5,
            'po_quantity_purchased' => 0,
            'purchase_price' => 10,
            'purchase_price_inc_tax' => 10,
            'item_tax' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTables(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedInteger('location_id')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('payment_status', 50)->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedBigInteger('return_parent_id')->nullable();
            $table->string('ref_no')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('source')->nullable();
            $table->date('transaction_date')->nullable();
            $table->decimal('final_total', 22, 4)->default(0);
            $table->decimal('tax_amount', 22, 4)->default(0);
            $table->json('purchase_order_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('variation_id')->nullable();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('quantity_returned', 22, 4)->default(0);
            $table->decimal('po_quantity_purchased', 22, 4)->default(0);
            $table->unsignedBigInteger('purchase_order_line_id')->nullable();
            $table->decimal('purchase_price', 22, 4)->default(0);
            $table->decimal('purchase_price_inc_tax', 22, 4)->default(0);
            $table->decimal('item_tax', 22, 4)->default(0);
            $table->string('lot_number')->nullable();
            $table->date('exp_date')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('method')->nullable();
            $table->string('payment_ref_no')->nullable();
            $table->string('transaction_no')->nullable();
            $table->date('paid_on')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('storage_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('parent_document_id')->nullable();
            $table->string('document_type', 40);
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('workflow_state', 40)->default('draft');
            $table->string('sync_status', 30)->default('not_required');
            $table->unsignedBigInteger('vas_inventory_document_id')->nullable();
            $table->string('document_no', 60);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_document_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedInteger('line_no')->default(1);
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('document_line_id')->nullable();
            $table->string('task_type', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('storage_task_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_document_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('linked_system', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('storage_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('linked_system', 40)->nullable();
            $table->string('action', 40)->nullable();
            $table->string('status', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('storage_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('document_line_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('document_line_id')->nullable();
            $table->timestamps();
        });
    }
}
