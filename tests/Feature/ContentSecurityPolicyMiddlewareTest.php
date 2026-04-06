<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ContentSecurityPolicyMiddlewareTest extends TestCase
{
    public function test_home_response_includes_content_security_policy(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('Content-Security-Policy');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    public function test_when_disabled_header_is_not_set(): void
    {
        Config::set('csp.enabled', false);

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    public function test_report_only_sends_report_only_header(): void
    {
        Config::set('csp.report_only', true);

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $response->assertHeader('Content-Security-Policy-Report-Only');
    }
}
