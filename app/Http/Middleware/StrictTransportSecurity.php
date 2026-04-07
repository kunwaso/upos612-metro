<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StrictTransportSecurity
{
    /**
     * Add Strict-Transport-Security for HTTPS responses when enabled.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('hsts.enabled', true)) {
            return $response;
        }

        if (! $request->secure()) {
            return $response;
        }

        $maxAge = max(0, (int) config('hsts.max_age', 31536000));
        $directives = ['max-age='.$maxAge];

        if (config('hsts.include_subdomains', false)) {
            $directives[] = 'includeSubDomains';
        }

        if (config('hsts.preload', false)) {
            $directives[] = 'preload';
        }

        $response->headers->set('Strict-Transport-Security', implode('; ', $directives));

        return $response;
    }
}
