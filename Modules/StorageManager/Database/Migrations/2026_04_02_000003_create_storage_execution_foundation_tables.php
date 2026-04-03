<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $this->createStorageAreasTable();
        $this->extendStorageSlotsTable();
        $this->createStorageLocationSettingsTable();
        $this->createStorageSlotStockTable();
        $this->createStorageInventoryMovementsTable();
        $this->createStorageDocumentsTable();
        $this->createStorageDocumentLinesTable();
        $this->createStorageTasksTable();
        $this->createStorageTaskEventsTable();
        $this->createStorageApprovalRequestsTable();
        $this->createStorageCountSessionsTable();
        $this->createStorageCountLinesTable();
        $this->createStorageReplenishmentRulesTable();
        $this->createStorageDocumentLinksTable();
        $this->createStorageSyncLogsTable();
        $this->backfillLegacyAreas();
    }

    public function down()
    {
        Schema::dropIfExists('storage_sync_logs');
        Schema::dropIfExists('storage_document_links');
        Schema::dropIfExists('storage_replenishment_rules');
        Schema::dropIfExists('storage_count_lines');
        Schema::dropIfExists('storage_count_sessions');
        Schema::dropIfExists('storage_approval_requests');
        Schema::dropIfExists('storage_task_events');
        Schema::dropIfExists('storage_tasks');
        Schema::dropIfExists('storage_document_lines');
        Schema::dropIfExists('storage_documents');
        Schema::dropIfExists('storage_inventory_movements');
        Schema::dropIfExists('storage_slot_stock');
        Schema::dropIfExists('storage_location_settings');

        if (Schema::hasTable('storage_slots')) {
            Schema::table('storage_slots', function (Blueprint $table) {
                if (Schema::hasColumn('storage_slots', 'area_id')) {
                    $table->dropIndex(['area_id', 'status']);
                    $table->dropColumn([
                        'area_id',
                        'barcode',
                        'slot_type',
                        'status',
                        'pick_sequence',
                        'putaway_sequence',
                        'allows_mixed_sku',
                        'allows_mixed_lot',
                        'meta',
                    ]);
                }
            });
        }

        Schema::dropIfExists('storage_areas');
    }

    protected function createStorageAreasTable(): void
    {
        if (Schema::hasTable('storage_areas')) {
            return;
        }

        Schema::create('storage_areas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 120);
            $table->string('code', 60)->nullable();
            $table->string('area_type', 40)->default('reserve');
            $table->string('status', 20)->default('active');
            $table->string('barcode', 120)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'location_id', 'area_type'], 'storage_areas_location_type_idx');
            $table->index(['business_id', 'category_id'], 'storage_areas_category_idx');
        });
    }

    protected function extendStorageSlotsTable(): void
    {
        if (! Schema::hasTable('storage_slots')) {
            return;
        }

        Schema::table('storage_slots', function (Blueprint $table) {
            if (! Schema::hasColumn('storage_slots', 'area_id')) {
                $table->unsignedBigInteger('area_id')->nullable()->after('category_id');
            }
            if (! Schema::hasColumn('storage_slots', 'barcode')) {
                $table->string('barcode', 120)->nullable()->after('slot_code');
            }
            if (! Schema::hasColumn('storage_slots', 'slot_type')) {
                $table->string('slot_type', 30)->default('standard')->after('barcode');
            }
            if (! Schema::hasColumn('storage_slots', 'status')) {
                $table->string('status', 20)->default('active')->after('slot_type');
            }
            if (! Schema::hasColumn('storage_slots', 'pick_sequence')) {
                $table->unsignedInteger('pick_sequence')->default(0)->after('status');
            }
            if (! Schema::hasColumn('storage_slots', 'putaway_sequence')) {
                $table->unsignedInteger('putaway_sequence')->default(0)->after('pick_sequence');
            }
            if (! Schema::hasColumn('storage_slots', 'allows_mixed_sku')) {
                $table->boolean('allows_mixed_sku')->default(false)->after('putaway_sequence');
            }
            if (! Schema::hasColumn('storage_slots', 'allows_mixed_lot')) {
                $table->boolean('allows_mixed_lot')->default(false)->after('allows_mixed_sku');
            }
            if (! Schema::hasColumn('storage_slots', 'meta')) {
                $table->json('meta')->nullable()->after('allows_mixed_lot');
            }
        });

        Schema::table('storage_slots', function (Blueprint $table) {
            $table->index(['area_id', 'status'], 'storage_slots_area_status_idx');
        });
    }

    protected function createStorageLocationSettingsTable(): void
    {
        if (Schema::hasTable('storage_location_settings')) {
            return;
        }

        Schema::create('storage_location_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->string('execution_mode', 30)->default('off');
            $table->string('scanner_mode', 30)->default('browser_ready');
            $table->string('bypass_policy', 30)->default('report_only');
            $table->unsignedBigInteger('default_receiving_area_id')->nullable();
            $table->unsignedBigInteger('default_staging_area_id')->nullable();
            $table->unsignedBigInteger('default_packing_area_id')->nullable();
            $table->unsignedBigInteger('default_dispatch_area_id')->nullable();
            $table->unsignedBigInteger('default_quarantine_area_id')->nullable();
            $table->unsignedBigInteger('default_damaged_area_id')->nullable();
            $table->unsignedBigInteger('default_count_hold_area_id')->nullable();
            $table->boolean('require_lot_tracking')->default(false);
            $table->boolean('require_expiry_tracking')->default(false);
            $table->boolean('enforce_vas_sync')->default(false);
            $table->string('status', 20)->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'location_id'], 'storage_location_settings_location_unique');
            $table->index(['business_id', 'execution_mode'], 'storage_location_settings_mode_idx');
        });
    }

    protected function createStorageSlotStockTable(): void
    {
        if (Schema::hasTable('storage_slot_stock')) {
            return;
        }

        Schema::create('storage_slot_stock', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedInteger('slot_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id')->nullable();
            $table->string('stock_key', 190);
            $table->string('lot_number', 120)->default('');
            $table->date('expiry_date')->nullable();
            $table->string('inventory_status', 30)->default('available');
            $table->decimal('qty_on_hand', 22, 4)->default(0);
            $table->decimal('qty_reserved', 22, 4)->default(0);
            $table->decimal('qty_inbound', 22, 4)->default(0);
            $table->decimal('qty_outbound', 22, 4)->default(0);
            $table->decimal('qty_count_pending', 22, 4)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'stock_key'], 'storage_slot_stock_key_unique');
            $table->index(['business_id', 'location_id', 'variation_id'], 'storage_slot_stock_location_variation_idx');
            $table->index(['slot_id', 'inventory_status'], 'storage_slot_stock_slot_status_idx');
        });
    }

    protected function createStorageInventoryMovementsTable(): void
    {
        if (Schema::hasTable('storage_inventory_movements')) {
            return;
        }

        Schema::create('storage_inventory_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('document_line_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('movement_type', 40);
            $table->string('direction', 20)->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id')->nullable();
            $table->unsignedBigInteger('from_area_id')->nullable();
            $table->unsignedBigInteger('to_area_id')->nullable();
            $table->unsignedInteger('from_slot_id')->nullable();
            $table->unsignedInteger('to_slot_id')->nullable();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->string('lot_number', 120)->default('');
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 22, 4)->default(0);
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->string('reason_code', 60)->nullable();
            $table->string('idempotency_key', 120)->nullable();
            $table->timestamp('moved_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'idempotency_key'], 'storage_inventory_movements_idempotency_unique');
            $table->index(['business_id', 'location_id', 'product_id'], 'storage_inventory_movements_product_idx');
            $table->index(['source_type', 'source_id'], 'storage_inventory_movements_source_idx');
        });
    }

    protected function createStorageDocumentsTable(): void
    {
        if (Schema::hasTable('storage_documents')) {
            return;
        }

        Schema::create('storage_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('parent_document_id')->nullable();
            $table->string('document_no', 60);
            $table->string('document_type', 40);
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_ref', 120)->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('workflow_state', 40)->default('draft');
            $table->string('execution_mode', 30)->nullable();
            $table->string('sync_status', 30)->default('not_required');
            $table->unsignedBigInteger('vas_inventory_document_id')->nullable();
            $table->string('approval_status', 30)->default('not_required');
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('assigned_to')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('closed_by')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'document_no'], 'storage_documents_no_unique');
            $table->index(['business_id', 'document_type', 'status'], 'storage_documents_type_status_idx');
            $table->index(['source_type', 'source_id'], 'storage_documents_source_idx');
            $table->index(['business_id', 'sync_status'], 'storage_documents_sync_idx');
        });
    }

    protected function createStorageDocumentLinesTable(): void
    {
        if (Schema::hasTable('storage_document_lines')) {
            return;
        }

        Schema::create('storage_document_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedInteger('line_no');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->unsignedBigInteger('parent_line_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id')->nullable();
            $table->unsignedBigInteger('from_area_id')->nullable();
            $table->unsignedBigInteger('to_area_id')->nullable();
            $table->unsignedInteger('from_slot_id')->nullable();
            $table->unsignedInteger('to_slot_id')->nullable();
            $table->decimal('expected_qty', 22, 4)->default(0);
            $table->decimal('executed_qty', 22, 4)->default(0);
            $table->decimal('variance_qty', 22, 4)->default(0);
            $table->decimal('unit_cost', 22, 4)->default(0);
            $table->string('inventory_status', 30)->default('available');
            $table->string('result_status', 30)->default('pending');
            $table->string('lot_number', 120)->default('');
            $table->date('expiry_date')->nullable();
            $table->string('reason_code', 60)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'line_no'], 'storage_document_lines_document_idx');
            $table->index(['business_id', 'product_id'], 'storage_document_lines_product_idx');
        });
    }

    protected function createStorageTasksTable(): void
    {
        if (Schema::hasTable('storage_tasks')) {
            return;
        }

        Schema::create('storage_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedInteger('slot_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('document_line_id')->nullable();
            $table->string('task_type', 40);
            $table->string('status', 30)->default('open');
            $table->string('priority', 20)->default('normal');
            $table->string('required_scan_mode', 30)->default('optional');
            $table->string('queue_name', 40)->default('default');
            $table->unsignedInteger('assignee_id')->nullable();
            $table->unsignedInteger('requested_by')->nullable();
            $table->decimal('target_qty', 22, 4)->default(0);
            $table->decimal('completed_qty', 22, 4)->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status', 'assignee_id'], 'storage_tasks_status_assignee_idx');
            $table->index(['document_id', 'document_line_id'], 'storage_tasks_document_idx');
        });
    }

    protected function createStorageTaskEventsTable(): void
    {
        if (Schema::hasTable('storage_task_events')) {
            return;
        }

        Schema::create('storage_task_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('task_id');
            $table->string('event_type', 40);
            $table->string('idempotency_key', 120)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('device_ref', 120)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'event_type'], 'storage_task_events_task_idx');
            $table->index(['business_id', 'idempotency_key'], 'storage_task_events_idempotency_idx');
        });
    }

    protected function createStorageApprovalRequestsTable(): void
    {
        if (Schema::hasTable('storage_approval_requests')) {
            return;
        }

        Schema::create('storage_approval_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('document_line_id')->nullable();
            $table->string('approval_type', 40);
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('requested_by')->nullable();
            $table->unsignedInteger('assigned_to')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->unsignedInteger('rejected_by')->nullable();
            $table->decimal('threshold_value', 22, 4)->nullable();
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status', 'approval_type'], 'storage_approval_requests_status_idx');
        });
    }

    protected function createStorageCountSessionsTable(): void
    {
        if (Schema::hasTable('storage_count_sessions')) {
            return;
        }

        Schema::create('storage_count_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedBigInteger('area_id')->nullable();
            $table->string('session_no', 60);
            $table->string('status', 30)->default('planned');
            $table->string('freeze_mode', 30)->default('soft');
            $table->boolean('blind_count')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'session_no'], 'storage_count_sessions_no_unique');
            $table->index(['business_id', 'location_id', 'status'], 'storage_count_sessions_status_idx');
        });
    }

    protected function createStorageCountLinesTable(): void
    {
        if (Schema::hasTable('storage_count_lines')) {
            return;
        }

        Schema::create('storage_count_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('count_session_id');
            $table->unsignedInteger('slot_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id')->nullable();
            $table->string('inventory_status', 30)->default('available');
            $table->string('lot_number', 120)->default('');
            $table->date('expiry_date')->nullable();
            $table->decimal('system_qty', 22, 4)->default(0);
            $table->decimal('counted_qty', 22, 4)->nullable();
            $table->decimal('variance_qty', 22, 4)->default(0);
            $table->string('status', 30)->default('open');
            $table->unsignedInteger('counted_by')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->string('reason_code', 60)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['count_session_id', 'status'], 'storage_count_lines_status_idx');
        });
    }

    protected function createStorageReplenishmentRulesTable(): void
    {
        if (Schema::hasTable('storage_replenishment_rules')) {
            return;
        }

        Schema::create('storage_replenishment_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedBigInteger('source_area_id')->nullable();
            $table->unsignedBigInteger('destination_area_id')->nullable();
            $table->unsignedInteger('source_slot_id')->nullable();
            $table->unsignedInteger('destination_slot_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id')->nullable();
            $table->decimal('min_qty', 22, 4)->default(0);
            $table->decimal('max_qty', 22, 4)->default(0);
            $table->decimal('replenish_qty', 22, 4)->nullable();
            $table->string('trigger_mode', 30)->default('minmax');
            $table->string('status', 20)->default('active');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'location_id', 'status'], 'storage_replenishment_rules_status_idx');
        });
    }

    protected function createStorageDocumentLinksTable(): void
    {
        if (Schema::hasTable('storage_document_links')) {
            return;
        }

        Schema::create('storage_document_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('document_id');
            $table->string('linked_system', 40);
            $table->string('linked_type', 60)->nullable();
            $table->unsignedBigInteger('linked_id')->nullable();
            $table->string('linked_ref', 120)->nullable();
            $table->string('link_role', 40)->nullable();
            $table->string('sync_status', 30)->default('not_required');
            $table->timestamp('synced_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'linked_system'], 'storage_document_links_system_idx');
            $table->index(['linked_system', 'linked_type', 'linked_id'], 'storage_document_links_ref_idx');
        });
    }

    protected function createStorageSyncLogsTable(): void
    {
        if (Schema::hasTable('storage_sync_logs')) {
            return;
        }

        Schema::create('storage_sync_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('linked_system', 40);
            $table->string('action', 40);
            $table->string('status', 30);
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'linked_system', 'status'], 'storage_sync_logs_status_idx');
        });
    }

    protected function backfillLegacyAreas(): void
    {
        if (! Schema::hasTable('storage_slots') || ! Schema::hasTable('storage_areas')) {
            return;
        }

        $legacyGroups = DB::table('storage_slots')
            ->select('business_id', 'location_id', 'category_id')
            ->whereNotNull('category_id')
            ->groupBy('business_id', 'location_id', 'category_id')
            ->get();

        foreach ($legacyGroups as $group) {
            $areaId = DB::table('storage_areas')
                ->where('business_id', $group->business_id)
                ->where('location_id', $group->location_id)
                ->where('category_id', $group->category_id)
                ->value('id');

            if (! $areaId) {
                $code = sprintf('LEG-%s-%s', $group->location_id, $group->category_id);
                $areaId = DB::table('storage_areas')->insertGetId([
                    'business_id' => $group->business_id,
                    'location_id' => $group->location_id,
                    'category_id' => $group->category_id,
                    'name' => 'Legacy Zone ' . $group->category_id,
                    'code' => $code,
                    'area_type' => 'legacy_zone',
                    'status' => 'active',
                    'sort_order' => 0,
                    'meta' => json_encode(['backfilled' => true]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('storage_slots')
                ->where('business_id', $group->business_id)
                ->where('location_id', $group->location_id)
                ->where('category_id', $group->category_id)
                ->whereNull('area_id')
                ->update(['area_id' => $areaId]);
        }

        $locationRows = DB::table('storage_slots')
            ->select('business_id', 'location_id', 'area_id')
            ->whereNotNull('area_id')
            ->groupBy('business_id', 'location_id', 'area_id')
            ->get();

        foreach ($locationRows as $row) {
            DB::table('storage_location_settings')->updateOrInsert(
                [
                    'business_id' => $row->business_id,
                    'location_id' => $row->location_id,
                ],
                [
                    'default_staging_area_id' => $row->area_id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
};
