<?php

namespace Modules\Aichat\Tests\Unit;

use App\User;
use Modules\Aichat\Utils\ChatCapabilityResolver;
use Tests\TestCase;

class ChatCapabilityResolverTest extends TestCase
{
    public function test_resolver_returns_denied_capabilities_for_missing_user(): void
    {
        $resolver = new ChatCapabilityResolver();
        $caps = $resolver->resolveForUser(null, 99);

        $this->assertFalse((bool) data_get($caps, 'products.view'));
        $this->assertFalse((bool) data_get($caps, 'products.view_cost'));
        $this->assertFalse((bool) data_get($caps, 'contacts.customer.view'));
        $this->assertFalse((bool) data_get($caps, 'sales.create'));
        $this->assertFalse((bool) data_get($caps, 'quotes.view'));
        $this->assertFalse((bool) data_get($caps, 'reports.view'));
        $this->assertFalse((bool) data_get($caps, 'settings.access'));
        $this->assertSame(99, data_get($caps, '_meta.business_id'));
    }

    public function test_resolver_maps_existing_permission_names_to_capabilities(): void
    {
        $permissionMap = [
            'product.view' => true,
            'view_purchase_price' => true,
            'product.create' => true,
            'product.update' => false,
            'product.delete' => false,
            'customer.view' => true,
            'customer.view_own' => false,
            'customer.create' => false,
            'customer.update' => false,
            'customer.delete' => false,
            'supplier.view' => false,
            'supplier.view_own' => true,
            'supplier.create' => false,
            'supplier.update' => true,
            'supplier.delete' => false,
            'sell.view' => false,
            'direct_sell.view' => false,
            'view_own_sell_only' => true,
            'view_commission_agent_sell' => false,
            'sell.create' => true,
            'direct_sell.access' => false,
            'so.create' => false,
            'sell.update' => false,
            'direct_sell.update' => false,
            'so.update' => true,
            'sell.delete' => false,
            'direct_sell.delete' => false,
            'so.delete' => false,
            'purchase.view' => false,
            'view_own_purchase' => true,
            'purchase.create' => true,
            'purchase.update' => false,
            'purchase.delete' => true,
            'product_quote.view' => true,
            'product_quote.create' => false,
            'product_quote.edit' => true,
            'product_quote.delete' => false,
            'product_quote.send' => true,
            'product_quote.admin_override' => true,
            'profit_loss_report.view' => true,
            'business_settings.access' => true,
            'aichat.chat.settings' => true,
            'aichat.manage_all_memories' => false,
            'aichat.chat.view' => true,
            'aichat.chat.edit' => true,
            'aichat.quote_wizard.use' => false,
        ];

        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 7;
        $user->shouldReceive('can')->andReturnUsing(function (string $permission) use ($permissionMap): bool {
            return (bool) ($permissionMap[$permission] ?? false);
        });

        $resolver = new ChatCapabilityResolver();
        $caps = $resolver->resolveForUser($user, 44);

        $this->assertTrue((bool) data_get($caps, 'products.view'));
        $this->assertTrue((bool) data_get($caps, 'products.view_cost'));
        $this->assertTrue((bool) data_get($caps, 'products.create'));
        $this->assertFalse((bool) data_get($caps, 'products.update'));
        $this->assertTrue((bool) data_get($caps, 'contacts.customer.view'));
        $this->assertTrue((bool) data_get($caps, 'contacts.supplier.view_own'));
        $this->assertTrue((bool) data_get($caps, 'sales.view_own'));
        $this->assertTrue((bool) data_get($caps, 'sales.create'));
        $this->assertTrue((bool) data_get($caps, 'sales.update'));
        $this->assertTrue((bool) data_get($caps, 'purchases.view_own'));
        $this->assertTrue((bool) data_get($caps, 'purchases.delete'));
        $this->assertTrue((bool) data_get($caps, 'quotes.view'));
        $this->assertTrue((bool) data_get($caps, 'quotes.update'));
        $this->assertTrue((bool) data_get($caps, 'quotes.send'));
        $this->assertTrue((bool) data_get($caps, 'quotes.admin_override'));
        $this->assertTrue((bool) data_get($caps, 'reports.view'));
        $this->assertTrue((bool) data_get($caps, 'settings.access'));
        $this->assertTrue((bool) data_get($caps, 'settings.chat_settings'));
        $this->assertTrue((bool) data_get($caps, 'chat.edit'));
        $this->assertFalse((bool) data_get($caps, 'chat.quote_wizard'));
    }

    public function test_resolver_returns_actor_envelope_with_channel_context(): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 15;
        $user->shouldReceive('can')->andReturn(false);

        $resolver = new ChatCapabilityResolver();
        $envelope = $resolver->resolveForActor($user, 88, 'telegram');

        $this->assertSame(15, data_get($envelope, 'actor.user_id'));
        $this->assertSame(88, data_get($envelope, 'actor.business_id'));
        $this->assertSame('telegram', data_get($envelope, 'actor.channel'));
        $this->assertIsArray(data_get($envelope, 'domains'));
        $this->assertIsArray(data_get($envelope, 'actions.modules'));
    }

    public function test_resolver_maps_configured_extended_domain_capabilities(): void
    {
        config()->set('aichat.chat.capability_domain_map', [
            'storage' => [
                'view' => ['storage.view'],
                'transfer' => ['storage.transfer'],
            ],
        ]);

        $permissionMap = [
            'storage.view' => true,
            'storage.transfer' => false,
        ];

        $user = \Mockery::mock(User::class)->makePartial();
        $user->id = 9;
        $user->shouldReceive('can')->andReturnUsing(function (string $permission) use ($permissionMap): bool {
            return (bool) ($permissionMap[$permission] ?? false);
        });

        $resolver = new ChatCapabilityResolver();
        $envelope = $resolver->resolveForActor($user, 55);

        $this->assertTrue((bool) data_get($envelope, 'domains.storage.view'));
        $this->assertFalse((bool) data_get($envelope, 'domains.storage.transfer'));
    }
}
