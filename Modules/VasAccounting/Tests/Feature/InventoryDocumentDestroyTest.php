<?php

namespace Modules\VasAccounting\Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\VasAccounting\Entities\VasInventoryDocument;
use Modules\VasAccounting\Http\Controllers\InventoryController;
use Modules\VasAccounting\Services\DocumentApprovalService;
use Modules\VasAccounting\Services\SourceDocumentAdapterManager;
use Modules\VasAccounting\Services\VasInventoryValuationService;
use Modules\VasAccounting\Services\VasPostingService;
use Modules\VasAccounting\Services\VasWarehouseDocumentService;
use Modules\VasAccounting\Utils\InventoryDocumentLifecycleUtil;
use Modules\VasAccounting\Utils\LedgerPostingUtil;
use Modules\VasAccounting\Utils\OperationsAssetReportUtil;
use Modules\VasAccounting\Utils\VasAccountingUtil;
use Tests\TestCase;

class InventoryDocumentDestroyTest extends TestCase
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
        $this->bindControllerDependencies(Mockery::mock(InventoryDocumentLifecycleUtil::class)->shouldIgnoreMissing());
    }

    public function test_destroy_route_returns_forbidden_without_destroy_permission(): void
    {
        $this->actingAs($this->makeUser(['vas_accounting.inventory.manage']));

        $this->withSession(['user' => ['business_id' => 44]])
            ->delete(route('vasaccounting.inventory.documents.destroy', 999))
            ->assertForbidden();
    }

    public function test_destroy_route_returns_not_found_for_document_in_another_business(): void
    {
        $document = $this->createInventoryDocument([
            'business_id' => 99,
            'document_no' => 'WH-404',
        ]);

        $this->actingAs($this->makeUser(['vas_accounting.inventory.destroy_draft']));

        $this->withSession(['user' => ['business_id' => 44]])
            ->delete(route('vasaccounting.inventory.documents.destroy', $document->id))
            ->assertNotFound();
    }

    public function test_destroy_route_deletes_draft_document_and_clears_related_records(): void
    {
        $this->bindControllerDependencies($this->makeLifecycleUtil());

        $document = $this->createInventoryDocument([
            'document_no' => 'WH-0001',
            'status' => 'draft',
        ]);

        DB::table('vas_inventory_document_lines')->insert([
            'id' => 501,
            'business_id' => 44,
            'inventory_document_id' => $document->id,
            'line_no' => 1,
            'product_id' => 1001,
            'quantity' => 2,
            'unit_cost' => 10,
            'amount' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vas_vouchers')->insert([
            'id' => 601,
            'business_id' => 44,
            'voucher_no' => 'JV-0001',
            'status' => 'draft',
            'source_type' => 'inventory_document',
            'source_id' => $document->id,
            'version_no' => 1,
            'is_system_generated' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vas_document_approvals')->insert([
            'id' => 701,
            'business_id' => 44,
            'entity_type' => 'Modules\\VasAccounting\\Entities\\VasVoucher',
            'entity_id' => 601,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vas_document_audit_logs')->insert([
            'id' => 702,
            'business_id' => 44,
            'entity_type' => 'Modules\\VasAccounting\\Entities\\VasVoucher',
            'entity_id' => 601,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vas_document_attachments')->insert([
            'id' => 703,
            'business_id' => 44,
            'entity_type' => 'Modules\\VasAccounting\\Entities\\VasVoucher',
            'entity_id' => 601,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('storage_documents')->insert([
            'id' => 801,
            'business_id' => 44,
            'document_no' => 'RCV-001',
            'sync_status' => 'synced_unposted',
            'vas_inventory_document_id' => $document->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('storage_document_links')->insert([
            'id' => 802,
            'business_id' => 44,
            'document_id' => 801,
            'linked_system' => 'vas',
            'linked_type' => 'vas_inventory_document',
            'linked_id' => $document->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->makeUser(['vas_accounting.inventory.destroy_draft']));

        $response = $this->withSession(['user' => ['business_id' => 44]])
            ->delete(route('vasaccounting.inventory.documents.destroy', $document->id));

        $response->assertRedirect(route('vasaccounting.inventory.index'));
        $response->assertSessionHas('status', function (array $status) {
            return ($status['success'] ?? false) === true
                && ($status['msg'] ?? null) === __('vasaccounting::lang.inventory_document_deleted');
        });

        $this->assertDatabaseMissing('vas_inventory_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('vas_inventory_document_lines', ['inventory_document_id' => $document->id]);
        $this->assertDatabaseMissing('vas_vouchers', ['id' => 601]);
        $this->assertDatabaseMissing('vas_document_approvals', ['entity_id' => 601]);
        $this->assertDatabaseMissing('vas_document_audit_logs', ['entity_id' => 601]);
        $this->assertDatabaseMissing('vas_document_attachments', ['entity_id' => 601]);
        $this->assertDatabaseMissing('storage_document_links', ['linked_id' => $document->id]);
        $this->assertDatabaseHas('storage_documents', [
            'id' => 801,
            'vas_inventory_document_id' => null,
            'sync_status' => 'not_required',
        ]);
        $this->assertDatabaseHas('storage_sync_logs', [
            'document_id' => 801,
            'action' => 'admin_delete_inventory_document',
            'status' => 'not_required',
        ]);
    }

    public function test_destroy_route_refuses_posted_document(): void
    {
        $this->bindControllerDependencies($this->makeLifecycleUtil());

        $document = $this->createInventoryDocument([
            'document_no' => 'WH-POSTED',
            'status' => 'posted',
            'posted_voucher_id' => 900,
        ]);

        $this->actingAs($this->makeUser(['vas_accounting.inventory.destroy_draft']));

        $response = $this->withSession(['user' => ['business_id' => 44]])
            ->delete(route('vasaccounting.inventory.documents.destroy', $document->id));

        $response->assertRedirect(route('vasaccounting.inventory.documents.show', $document->id));
        $response->assertSessionHas('status', function (array $status) {
            return ($status['success'] ?? true) === false
                && ($status['msg'] ?? null) === __('vasaccounting::lang.inventory_document_delete_posted_requires_reverse');
        });

        $this->assertDatabaseHas('vas_inventory_documents', ['id' => $document->id]);
    }

    public function test_show_document_displays_delete_button_for_authorized_admin_when_eligible(): void
    {
        $lifecycleUtil = Mockery::mock(InventoryDocumentLifecycleUtil::class);
        $lifecycleUtil->shouldReceive('deleteEligibility')
            ->once()
            ->andReturn(['allowed' => true, 'reason' => null]);

        $this->bindControllerDependencies($lifecycleUtil);

        $document = $this->createInventoryDocument([
            'document_no' => 'WH-SHOW-OK',
        ]);

        $this->actingAs($this->makeUser([
            'vas_accounting.inventory.manage',
            'vas_accounting.inventory.destroy_draft',
        ]));

        $this->withSession(['user' => ['business_id' => 44]])
            ->get(route('vasaccounting.inventory.documents.show', $document->id))
            ->assertOk()
            ->assertSee(__('vasaccounting::lang.inventory_document_delete_button'))
            ->assertSee(__('vasaccounting::lang.inventory_document_delete_operator_note'));
    }

    public function test_show_document_displays_block_reason_for_authorized_admin_when_delete_is_not_allowed(): void
    {
        $lifecycleUtil = Mockery::mock(InventoryDocumentLifecycleUtil::class);
        $lifecycleUtil->shouldReceive('deleteEligibility')
            ->once()
            ->andReturn([
                'allowed' => false,
                'reason' => __('vasaccounting::lang.inventory_document_delete_posted_requires_reverse'),
            ]);

        $this->bindControllerDependencies($lifecycleUtil);

        $document = $this->createInventoryDocument([
            'document_no' => 'WH-SHOW-BLOCKED',
        ]);

        $this->actingAs($this->makeUser([
            'vas_accounting.inventory.manage',
            'vas_accounting.inventory.destroy_draft',
        ]));

        $this->withSession(['user' => ['business_id' => 44]])
            ->get(route('vasaccounting.inventory.documents.show', $document->id))
            ->assertOk()
            ->assertSee(__('vasaccounting::lang.inventory_document_delete_button'))
            ->assertSee(__('vasaccounting::lang.inventory_document_delete_posted_requires_reverse'));
    }

    public function test_show_document_hides_delete_controls_without_destroy_permission(): void
    {
        $lifecycleUtil = Mockery::mock(InventoryDocumentLifecycleUtil::class);
        $lifecycleUtil->shouldNotReceive('deleteEligibility');

        $this->bindControllerDependencies($lifecycleUtil);

        $document = $this->createInventoryDocument([
            'document_no' => 'WH-SHOW-HIDDEN',
        ]);

        $this->actingAs($this->makeUser(['vas_accounting.inventory.manage']));

        $this->withSession(['user' => ['business_id' => 44]])
            ->get(route('vasaccounting.inventory.documents.show', $document->id))
            ->assertOk()
            ->assertDontSee(__('vasaccounting::lang.inventory_document_delete_button'))
            ->assertDontSee(__('vasaccounting::lang.inventory_document_delete_operator_note'));
    }

    protected function createTables(): void
    {
        Schema::create('vas_inventory_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('accounting_period_id')->nullable();
            $table->string('document_no')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('posted_voucher_id')->nullable();
            $table->unsignedInteger('reversal_voucher_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('vas_inventory_document_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('inventory_document_id');
            $table->unsignedInteger('line_no');
            $table->unsignedInteger('product_id')->nullable();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('vas_vouchers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('voucher_no')->nullable();
            $table->string('status')->default('draft');
            $table->string('source_type')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->unsignedInteger('version_no')->default(1);
            $table->boolean('is_system_generated')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->unsignedInteger('posted_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedInteger('reversed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('vas_journal_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->nullable();
            $table->unsignedInteger('voucher_id');
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

        Schema::create('vas_accounting_periods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->nullable();
            $table->string('name')->nullable();
            $table->string('label')->nullable();
            $table->string('status')->default('open');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_documents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('document_no')->nullable();
            $table->string('sync_status')->nullable();
            $table->unsignedInteger('vas_inventory_document_id')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_document_links', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('document_id')->nullable();
            $table->string('linked_system');
            $table->string('linked_type');
            $table->unsignedInteger('linked_id');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_sync_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('document_id')->nullable();
            $table->string('linked_system')->nullable();
            $table->string('action')->nullable();
            $table->string('status')->nullable();
            $table->text('message')->nullable();
            $table->text('payload')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    protected function createInventoryDocument(array $overrides = []): VasInventoryDocument
    {
        return VasInventoryDocument::query()->create(array_merge([
            'business_id' => 44,
            'document_no' => 'WH-TEST',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    protected function bindControllerDependencies($lifecycleUtil): void
    {
        $vasUtil = Mockery::mock(VasAccountingUtil::class)->shouldIgnoreMissing();
        $vasUtil->shouldReceive('documentTypeLabel')->andReturnUsing(fn ($value) => (string) $value);
        $vasUtil->shouldReceive('documentStatusLabel')->andReturnUsing(fn ($value) => (string) $value);
        $vasUtil->shouldReceive('actionLabel')->andReturnUsing(fn ($value) => ucfirst((string) $value));

        $this->app->instance(VasInventoryValuationService::class, Mockery::mock(VasInventoryValuationService::class)->shouldIgnoreMissing());
        $this->app->instance(VasAccountingUtil::class, $vasUtil);
        $this->app->instance(OperationsAssetReportUtil::class, Mockery::mock(OperationsAssetReportUtil::class)->shouldIgnoreMissing());
        $this->app->instance(VasWarehouseDocumentService::class, Mockery::mock(VasWarehouseDocumentService::class)->shouldIgnoreMissing());
        $this->app->instance(InventoryDocumentLifecycleUtil::class, $lifecycleUtil);

        $this->app->forgetInstance(InventoryController::class);
    }

    protected function makeLifecycleUtil(): InventoryDocumentLifecycleUtil
    {
        $postingService = new VasPostingService(
            Mockery::mock(SourceDocumentAdapterManager::class),
            Mockery::mock(VasAccountingUtil::class),
            Mockery::mock(LedgerPostingUtil::class),
            Mockery::mock(DocumentApprovalService::class)
        );

        return new InventoryDocumentLifecycleUtil($postingService);
    }

    protected function makeUser(array $allowedAbilities): User
    {
        return new class($allowedAbilities) extends User
        {
            protected array $allowedAbilities = [];

            public function __construct(array $allowedAbilities)
            {
                parent::__construct();
                $this->id = 7;
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

            public function can($ability, $arguments = []): bool
            {
                return in_array((string) $ability, $this->allowedAbilities, true);
            }
        };
    }
}
