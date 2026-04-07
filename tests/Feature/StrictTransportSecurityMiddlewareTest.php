<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StrictTransportSecurityMiddlewareTest extends TestCase
{
    public function test_https_response_includes_strict_transport_security(): void
    {
        Config::set('hsts.enabled', true);
        Config::set('hsts.max_age', 31536000);
        Config::set('hsts.include_subdomains', false);
        Config::set('hsts.preload', false);

        $response = $this->call('GET', '/', [], [], [], [
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_http_response_does_not_include_hsts(): void
    {
        Config::set('hsts.enabled', true);
        $savedUrl = config('app.url');
        $wasHttps = is_string($savedUrl) && str_starts_with($savedUrl, 'https://');

        Config::set('app.url', 'http://localhost');
        URL::forceScheme('http');

        try {
            $response = $this->get('/');

            $response->assertStatus(200);
            $this->assertFalse($response->headers->has('Strict-Transport-Security'));
        } finally {
            Config::set('app.url', $savedUrl);
            URL::forceScheme($wasHttps ? 'https' : null);
        }
    }

    public function test_when_disabled_header_is_not_set_even_on_https(): void
    {
        Config::set('hsts.enabled', false);

        $response = $this->call('GET', '/', [], [], [], [
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_optional_directives_are_appended(): void
    {
        Config::set('hsts.enabled', true);
        Config::set('hsts.max_age', 60);
        Config::set('hsts.include_subdomains', true);
        Config::set('hsts.preload', true);

        $response = $this->call('GET', '/', [], [], [], [
            'HTTPS' => 'on',
            'SERVER_PORT' => '443',
        ]);

        $response->assertStatus(200);
        $this->assertSame(
            'max-age=60; includeSubDomains; preload',
            $response->headers->get('Strict-Transport-Security')
        );
    }
}
