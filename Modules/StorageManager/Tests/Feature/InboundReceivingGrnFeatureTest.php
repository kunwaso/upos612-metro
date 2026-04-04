<?php

namespace Modules\StorageManager\Tests\Feature;

use App\Transaction;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\StorageManager\Entities\StorageDocument;
use Modules\StorageManager\Services\ReceivingService;
use Modules\StorageManager\Services\WarehouseSyncService;
use Modules\StorageManager\Utils\StorageManagerUtil;
use Modules\StorageManager\Utils\StorageVasReceiptSyncUtil;
use Tests\TestCase;

class InboundReceivingGrnFeatureTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createTables();
        $this->bindControllerDependencies();
    }

    public function test_start_purchase_order_receiving_route_returns_forbidden_without_operate_permission(): void
    {
        $this->actingAs($this->makeUser([]));

        $this->withSession(['user' => ['business_id' => 44, 'id' => 7]])
            ->post(route('storage-manager.inbound.purchase-orders.start-receiving', 55))
            ->assertForbidden();
    }

    public function test_start_purchase_order_receiving_route_redirects_to_generated_purchase_workbench(): void
    {
        $receivingService = Mockery::mock(ReceivingService::class);
        $receivingService->shouldReceive('startPurchaseOrderReceiving')
            ->once()
            ->with(44, 55, 7)
            ->andReturn(new Transaction([
                'id' => 91,
                'type' => 'purchase',
            ]));
        $this->bindControllerDependencies($receivingService);

        $this->actingAs($this->makeUser(['storage_manager.operate']));

        $response = $this->withSession(['user' => ['business_id' => 44, 'id' => 7]])
            ->post(route('storage-manager.inbound.purchase-orders.start-receiving', 55));

        $response->assertRedirect(route('storage-manager.inbound.show', [
            'sourceType' => 'purchase',
            'sourceId' => 91,
        ]));
    }

    public function test_grn_route_returns_not_found_for_document_in_another_business(): void
    {
        DB::table('storage_documents')->insert([
            'id' => 501,
            'business_id' => 99,
            'document_type' => 'receipt',
            'status' => 'completed',
            'source_type' => 'purchase',
            'source_id' => 77,
            'document_no' => 'RCV-0501',
            'meta' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->makeUser(['storage_manager.view']));

        $this->withSession(['user' => ['business_id' => 44, 'id' => 7]])
            ->get(route('storage-manager.inbound.grn.show', 501))
            ->assertNotFound();
    }

    public function test_grn_route_renders_goods_received_note_for_completed_purchase_receipt(): void
    {
        DB::table('storage_documents')->insert([
            'id' => 601,
            'business_id' => 44,
            'document_type' => 'receipt',
            'status' => 'completed',
            'source_type' => 'purchase',
            'source_id' => 77,
            'document_no' => 'RCV-0601',
            'meta' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $document = StorageDocument::query()->findOrFail(601);
        $sourceDocument = new Transaction([
            'id' => 77,
            'ref_no' => 'PUR-0077',
        ]);
        $sourceDocument->setRelation('location', (object) ['name' => 'Main Warehouse']);

        $receivingService = Mockery::mock(ReceivingService::class);
        $receivingService->shouldReceive('goodsReceivedNoteContext')
            ->once()
            ->with(44, Mockery::on(fn (StorageDocument $model) => (int) $model->id === 601))
            ->andReturn([
                'document' => $document,
                'sourceDocument' => $sourceDocument,
                'grn' => [
                    'grn_number' => 'RCV-0601',
                    'grn_date' => '2026-04-03',
                    'delivery_note_number' => 'DN-123',
                    'delivery_date' => '2026-04-03',
                    'carrier_driver_name' => 'Somchai',
                    'received_by_name' => 'Warehouse Admin',
                    'receiving_department' => 'Receiving',
                    'received_condition' => 'Good condition',
                    'comments' => 'Checked and accepted.',
                ],
                'supplierName' => 'ACME Supplies',
                'supplierAddress' => '123 Supplier Rd',
                'supplierContact' => '0123456789',
                'items' => [[
                    'item' => 'Blue Widget',
                    'description' => 'Batch A',
                    'unit_of_measure' => 'pcs',
                    'quantity_ordered' => 10,
                    'quantity_received' => 10,
                    'unit_price' => 12.5,
                    'total_price' => 125,
                ]],
                'totalItems' => 10,
                'totalAmount' => 125,
            ]);
        $this->bindControllerDependencies($receivingService);

        $this->actingAs($this->makeUser(['storage_manager.view']));

        $this->withSession(['user' => ['business_id' => 44, 'id' => 7]])
            ->get(route('storage-manager.inbound.grn.show', 601))
            ->assertOk()
            ->assertSee('GOODS RECEIVED NOTE')
            ->assertSee('RCV-0601')
            ->assertSee('ACME Supplies')
            ->assertSee('Blue Widget');
    }

    public function test_confirm_route_allows_generated_purchase_receipt_without_purchase_update_permission(): void
    {
        DB::table('storage_documents')->insert([
            'id' => 701,
            'business_id' => 44,
            'document_type' => 'receipt',
            'status' => 'open',
            'source_type' => 'purchase',
            'source_id' => 88,
            'document_no' => 'RCV-0701',
            'meta' => json_encode([
                'storage_manager' => [
                    'generated_from_purchase_order' => true,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receivingService = Mockery::mock(ReceivingService::class);
        $receivingService->shouldReceive('loadSourceDocument')
            ->once()
            ->andReturn(new Transaction([
                'id' => 88,
                'status' => 'pending',
            ]));
        $receivingService->shouldReceive('confirmReceipt')
            ->once()
            ->with(
                44,
                Mockery::on(fn (StorageDocument $model) => (int) $model->id === 701),
                Mockery::on(fn (array $payload) => ! empty($payload['delivery_note_number'])),
                7,
                true
            )
            ->andReturn(new StorageDocument([
                'source_type' => 'purchase',
                'source_id' => 88,
            ]));
        $this->bindControllerDependencies($receivingService);

        $this->actingAs($this->makeUser(['storage_manager.operate']));

        $response = $this->withSession(['user' => ['business_id' => 44, 'id' => 7]])
            ->post(route('storage-manager.inbound.confirm', 701), [
                'delivery_note_number' => 'DN-701',
                'lines' => [
                    1 => [
                        'executed_qty' => 5,
                        'lot_number' => 'LOT-1',
                        'expiry_date' => '2026-05-01',
                        'staging_slot_id' => 9,
                    ],
                ],
            ]);

        $response->assertRedirect(route('storage-manager.inbound.show', [
            'sourceType' => 'purchase',
            'sourceId' => 88,
        ]));
    }

    protected function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('storage_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('document_type', 40);
            $table->string('status', 30)->default('draft');
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('document_no', 60);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    protected function bindControllerDependencies(?ReceivingService $receivingService = null): void
    {
        $this->app->instance(ReceivingService::class, $receivingService ?: Mockery::mock(ReceivingService::class)->shouldIgnoreMissing());
        $this->app->instance(StorageManagerUtil::class, Mockery::mock(StorageManagerUtil::class)->shouldIgnoreMissing());
        $this->app->instance(WarehouseSyncService::class, Mockery::mock(WarehouseSyncService::class)->shouldIgnoreMissing());
        $this->app->instance(StorageVasReceiptSyncUtil::class, Mockery::mock(StorageVasReceiptSyncUtil::class)->shouldIgnoreMissing());
    }

    protected function makeUser(array $allowedAbilities): User
    {
        return new class($allowedAbilities) extends User
        {
            protected array $abilities = [];

            public function __construct(array $abilities = [])
            {
                parent::__construct();
                $this->id = 7;
                $this->business_id = 44;
                $this->abilities = array_fill_keys($abilities, true);
            }

            public function hasPermissionTo($permission, $guardName = null): bool
            {
                return $this->can($permission);
            }

            public function checkPermissionTo($permission, $guardName = null): bool
            {
                return $this->can($permission);
            }

            public function can($ability, $arguments = []): bool
            {
                return (bool) ($this->abilities[$ability] ?? false);
            }
        };
    }
}
