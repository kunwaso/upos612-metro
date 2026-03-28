<?php

namespace Modules\VasAccounting\Tests\Unit;

use Tests\TestCase;

class EnterpriseDomainConfigTest extends TestCase
{
    public function test_enterprise_domain_config_contains_required_domains(): void
    {
        $domains = config('vasaccounting.enterprise_domains', []);

        $this->assertArrayHasKey('cash_bank', $domains);
        $this->assertArrayHasKey('invoices', $domains);
        $this->assertArrayHasKey('contracts', $domains);
        $this->assertArrayHasKey('loans', $domains);
        $this->assertArrayHasKey('budgets', $domains);
        $this->assertArrayHasKey('integrations', $domains);
    }

    public function test_each_enterprise_domain_declares_route_and_permission(): void
    {
        foreach ((array) config('vasaccounting.enterprise_domains', []) as $domain => $config) {
            $this->assertNotEmpty($config['route'] ?? null, "Domain [{$domain}] is missing a route.");
            $this->assertNotEmpty($config['permission'] ?? null, "Domain [{$domain}] is missing a permission.");
            $this->assertNotEmpty($config['title'] ?? null, "Domain [{$domain}] is missing a title.");
        }
    }
}
