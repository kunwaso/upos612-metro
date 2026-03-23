<?php

namespace Modules\Aichat\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Aichat\Utils\ChatActionUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatActionUtilPermissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('type')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('user_contact_access', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('type')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('product_quotes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product_quotes');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('user_contact_access');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('products');
        DB::disconnect('sqlite');

        \Mockery::close();

        parent::tearDown();
    }

    public function test_contact_action_catalog_does_not_enable_mutation_from_view_own_only_capabilities(): void
    {
        $chatActionUtil = new ChatActionUtil(\Mockery::mock(ChatUtil::class));

        $catalog = $chatActionUtil->getActionCatalog([
            'contacts' => [
                'customer' => ['view_own' => true],
                'supplier' => ['view_own' => true],
            ],
        ]);

        $contactsModule = collect($catalog)->firstWhere('module', 'contacts');
        $this->assertNotNull($contactsModule);

        $actions = collect((array) ($contactsModule['actions'] ?? []))
            ->keyBy('action')
            ->all();

        $this->assertFalse((bool) data_get($actions, 'create.enabled', true));
        $this->assertFalse((bool) data_get($actions, 'update.enabled', true));
        $this->assertFalse((bool) data_get($actions, 'delete.enabled', true));
    }

    public function test_assert_action_allowed_rejects_contact_create_with_view_own_only_capability(): void
    {
        $chatActionUtil = new ChatActionUtil(\Mockery::mock(ChatUtil::class));

        try {
            $this->invokeProtected(
                $chatActionUtil,
                'assertActionAllowed',
                [
                    44,
                    5,
                    'contacts',
                    'create',
                    ['type' => 'customer', 'name' => 'No Perm', 'mobile' => '123'],
                    [
                        'chat' => ['edit' => true],
                        'contacts' => [
                            'customer' => ['view_own' => true],
                            'supplier' => ['view_own' => true],
                        ],
                    ],
                ]
            );
            $this->fail('Expected contact create to be denied when only view_own is granted.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(__('aichat::lang.chat_action_forbidden'), $exception->getMessage());
        }
    }

    public function test_assert_action_allowed_requires_both_permissions_for_both_contact_create(): void
    {
        $chatActionUtil = new ChatActionUtil(\Mockery::mock(ChatUtil::class));

        try {
            $this->invokeProtected(
                $chatActionUtil,
                'assertActionAllowed',
                [
                    44,
                    5,
                    'contacts',
                    'create',
                    ['type' => 'both', 'name' => 'Mixed', 'mobile' => '123'],
                    [
                        'chat' => ['edit' => true],
                        'contacts' => [
                            'customer' => ['create' => true],
                            'supplier' => ['create' => false],
                        ],
                    ],
                ]
            );
            $this->fail('Expected contact create(type=both) to require both customer.create and supplier.create.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(__('aichat::lang.chat_action_forbidden'), $exception->getMessage());
        }

        $this->invokeProtected(
            $chatActionUtil,
            'assertActionAllowed',
            [
                44,
                5,
                'contacts',
                'create',
                ['type' => 'both', 'name' => 'Mixed', 'mobile' => '123'],
                [
                    'chat' => ['edit' => true],
                    'contacts' => [
                        'customer' => ['create' => true],
                        'supplier' => ['create' => true],
                    ],
                ],
            ]
        );

        $this->assertTrue(true);
    }

    public function test_execute_report_action_only_returns_counts_for_authorized_modules(): void
    {
        DB::table('products')->insert([
            'business_id' => 44,
            'name' => 'Hidden Product',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contacts')->insert([
            'business_id' => 44,
            'type' => 'customer',
            'created_by' => 5,
            'name' => 'Hidden Contact',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            [
                'business_id' => 44,
                'type' => 'sell',
                'created_by' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => 44,
                'type' => 'sell',
                'created_by' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => 44,
                'type' => 'purchase',
                'created_by' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => 44,
                'type' => 'purchase',
                'created_by' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('product_quotes')->insert([
            'business_id' => 44,
            'created_by' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $capabilities = [
            'reports' => ['view' => true],
            'products' => ['view' => false],
            'contacts' => [
                'customer' => ['view' => false, 'view_own' => false],
                'supplier' => ['view' => false, 'view_own' => false],
            ],
            'sales' => ['view' => true, 'view_own' => false],
            'purchases' => ['view' => false, 'view_own' => true],
            'quotes' => ['view' => false],
        ];

        $chatUtil = \Mockery::mock(ChatUtil::class);

        $chatActionUtil = new ChatActionUtil($chatUtil);
        $result = (array) $this->invokeProtected($chatActionUtil, 'executeReportAction', [44, 5, 'run', [], $capabilities]);
        $data = (array) ($result['data'] ?? []);

        $this->assertSame('report', (string) ($result['entity'] ?? ''));
        $this->assertArrayNotHasKey('products_count', $data);
        $this->assertArrayNotHasKey('contacts_count', $data);
        $this->assertArrayNotHasKey('quotes_count', $data);
        $this->assertSame(2, (int) ($data['sales_count'] ?? 0));
        $this->assertSame(1, (int) ($data['purchases_count'] ?? 0));
    }

    public function test_serialize_pending_action_redacts_sensitive_payload_values(): void
    {
        $chatActionUtil = new ChatActionUtil(\Mockery::mock(ChatUtil::class));
        $pendingAction = new \Modules\Aichat\Entities\ChatPendingAction([
            'id' => 7,
            'module' => 'settings',
            'action' => 'update',
            'status' => 'pending',
            'channel' => 'web',
            'payload' => [
                'password' => 'super-secret',
                'access_token' => 'Bearer abc123',
                'safe_key' => 'safe-value',
            ],
            'result_payload' => [
                'api_key' => 'sk-123456789',
                'message' => 'done',
            ],
            'preview_text' => 'Update settings',
        ]);

        $serialized = $chatActionUtil->serializePendingAction($pendingAction);

        $this->assertSame('[redacted]', data_get($serialized, 'payload.password'));
        $this->assertSame('[redacted]', data_get($serialized, 'payload.access_token'));
        $this->assertSame('safe-value', data_get($serialized, 'payload.safe_key'));
        $this->assertSame('[redacted]', data_get($serialized, 'result_payload.api_key'));
    }

    protected function invokeProtected(object $target, string $method, array $arguments = [])
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }
}
