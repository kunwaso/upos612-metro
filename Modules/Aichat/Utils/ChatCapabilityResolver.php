<?php

namespace Modules\Aichat\Utils;

use App\User;

class ChatCapabilityResolver
{
    public function resolveForUser(?User $user, int $business_id): array
    {
        $can = function (string $permission) use ($user): bool {
            return $user ? (bool) $user->can($permission) : false;
        };

        $hasAny = function (array $permissions) use ($can): bool {
            foreach ($permissions as $permission) {
                if ($can($permission)) {
                    return true;
                }
            }

            return false;
        };

        $reportPermissions = [
            'profit_loss_report.view',
            'purchase_n_sell_report.view',
            'contacts_report.view',
            'stock_report.view',
            'tax_report.view',
            'trending_product_report.view',
            'expense_report.view',
            'register_report.view',
            'sales_representative.view',
            'report.stock_details',
        ];

        return [
            'products' => [
                'view' => $can('product.view'),
                'view_cost' => $can('view_purchase_price'),
                'create' => $can('product.create'),
                'update' => $can('product.update'),
                'delete' => $can('product.delete'),
            ],
            'contacts' => [
                'customer' => [
                    'view' => $can('customer.view'),
                    'view_own' => $can('customer.view_own'),
                    'create' => $can('customer.create'),
                    'update' => $can('customer.update'),
                    'delete' => $can('customer.delete'),
                ],
                'supplier' => [
                    'view' => $can('supplier.view'),
                    'view_own' => $can('supplier.view_own'),
                    'create' => $can('supplier.create'),
                    'update' => $can('supplier.update'),
                    'delete' => $can('supplier.delete'),
                ],
            ],
            'sales' => [
                'view' => $hasAny(['sell.view', 'direct_sell.view', 'so.view_all']),
                'view_own' => $hasAny(['view_own_sell_only', 'view_commission_agent_sell', 'so.view_own']),
                'create' => $hasAny(['sell.create', 'direct_sell.access', 'so.create']),
                'update' => $hasAny(['sell.update', 'direct_sell.update', 'so.update']),
                'delete' => $hasAny(['sell.delete', 'direct_sell.delete', 'so.delete']),
            ],
            'purchases' => [
                'view' => $can('purchase.view'),
                'view_own' => $can('view_own_purchase'),
                'create' => $can('purchase.create'),
                'update' => $can('purchase.update'),
                'delete' => $can('purchase.delete'),
            ],
            'quotes' => [
                'view' => $can('product_quote.view'),
                'create' => $can('product_quote.create'),
                'update' => $can('product_quote.edit'),
                'delete' => $can('product_quote.delete'),
                'send' => $can('product_quote.send'),
                'admin_override' => $can('product_quote.admin_override'),
            ],
            'reports' => [
                'view' => $hasAny($reportPermissions),
                'export' => $hasAny($reportPermissions),
            ],
            'settings' => [
                'access' => $can('business_settings.access'),
                'chat_settings' => $can('aichat.chat.settings'),
                'manage_all_memories' => $can('aichat.manage_all_memories'),
            ],
            'chat' => [
                'view' => $can('aichat.chat.view'),
                'edit' => $can('aichat.chat.edit'),
                'settings' => $can('aichat.chat.settings'),
                'quote_wizard' => $can('aichat.quote_wizard.use'),
            ],
            '_meta' => [
                'business_id' => $business_id,
            ],
        ];
    }
}
