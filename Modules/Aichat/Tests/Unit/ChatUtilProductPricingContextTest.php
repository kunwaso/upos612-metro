<?php

namespace Modules\Aichat\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Aichat\Utils\ChatAuditUtil;
use Modules\Aichat\Utils\ChatMessageRendererUtil;
use Modules\Aichat\Utils\ChatUtil;
use Tests\TestCase;

class ChatUtilProductPricingContextTest extends TestCase
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
            $table->string('sku')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('variations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->decimal('default_purchase_price', 20, 4)->nullable();
            $table->decimal('default_sell_price', 20, 4)->nullable();
            $table->decimal('sell_price_inc_tax', 20, 4)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('variations');
        Schema::dropIfExists('products');
        DB::disconnect('sqlite');
        \Mockery::close();

        parent::tearDown();
    }

    public function test_build_products_context_includes_unit_and_selling_price_and_cost_when_allowed(): void
    {
        DB::table('products')->insert([
            [
                'id' => 10,
                'business_id' => 44,
                'name' => 'PK-0002',
                'sku' => 'PK-0002',
                'type' => 'single',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'business_id' => 44,
                'name' => 'PK-0003',
                'sku' => 'PK-0003',
                'type' => 'single',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'business_id' => 99,
                'name' => 'Other',
                'sku' => 'OTH-001',
                'type' => 'single',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('variations')->insert([
            'product_id' => 10,
            'default_purchase_price' => 8.25,
            'default_sell_price' => 12.50,
            'sell_price_inc_tax' => 13.38,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chatUtil = $this->makeChatUtil();
        $context = (string) $this->invokeProtected($chatUtil, 'buildProductsContext', [44, true, true]);

        $this->assertStringContainsString('Products count: 2', $context);
        $this->assertStringContainsString('Product #10: PK-0002 | sku=PK-0002 | type=single | unit_price=12.50 | selling_price=13.38 | cost=8.25', $context);
        $this->assertStringContainsString('Product #11: PK-0003 | sku=PK-0003 | type=single | unit_price=- | selling_price=- | cost=-', $context);
        $this->assertStringNotContainsString('OTH-001', $context);
    }

    public function test_build_products_context_hides_cost_when_not_permitted(): void
    {
        DB::table('products')->insert([
            'id' => 12,
            'business_id' => 44,
            'name' => 'PK-0004',
            'sku' => 'PK-0004',
            'type' => 'single',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('variations')->insert([
            'product_id' => 12,
            'default_purchase_price' => 5.11,
            'default_sell_price' => 9.90,
            'sell_price_inc_tax' => 10.59,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chatUtil = $this->makeChatUtil();
        $context = (string) $this->invokeProtected($chatUtil, 'buildProductsContext', [44, true, false]);

        $this->assertStringContainsString('Product #12: PK-0004 | sku=PK-0004 | type=single | unit_price=9.90 | selling_price=10.59', $context);
        $this->assertStringNotContainsString('| cost=', $context);
    }

    public function test_build_products_context_returns_empty_when_view_is_denied(): void
    {
        $chatUtil = $this->makeChatUtil();
        $context = (string) $this->invokeProtected($chatUtil, 'buildProductsContext', [44, false, true]);

        $this->assertSame('', $context);
    }

    protected function makeChatUtil(): ChatUtil
    {
        return new ChatUtil(
            \Mockery::mock(ChatAuditUtil::class),
            \Mockery::mock(ChatMessageRendererUtil::class)
        );
    }

    protected function invokeProtected(object $target, string $method, array $arguments = [])
    {
        $reflection = new \ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($target, $arguments);
    }
}
