<?php

/**
 * Content-Security-Policy defaults for browser-facing responses.
 *
 * Legacy-friendly baseline: allows inline scripts/styles common in Metronic/Blade.
 * Tighten over time (nonces/hashes, drop unsafe-inline) and adjust connect-src for
 * Pusher, payment SDKs, maps, etc.
 *
 * @see https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html
 */

$defaultPolicy = implode(' ', [
    "default-src 'self'",
    "base-uri 'self'",
    "form-action 'self'",
    "frame-ancestors 'self'",
    "object-src 'none'",
    'upgrade-insecure-requests',
    "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' data: https:",
    "font-src 'self' data:",
    "connect-src 'self' https: wss:",
    "worker-src 'self' blob:",
    "manifest-src 'self'",
]);

$custom = env('CSP_POLICY');

return [
    'enabled' => filter_var(env('CSP_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'report_only' => filter_var(env('CSP_REPORT_ONLY', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Full policy string
    |--------------------------------------------------------------------------
    |
    | Set CSP_POLICY in .env to override the default entirely (e.g. after tuning).
    |
    */
    'policy' => ($custom !== null && $custom !== '') ? $custom : $defaultPolicy,
];
