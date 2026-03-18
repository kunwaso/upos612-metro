<?php

$defaultCaBundle = '';

foreach ([
    env('AICHAT_HTTP_CA_BUNDLE'),
    base_path('vendor/composer/ca-bundle/res/cacert.pem'),
    base_path('vendor/rmccue/requests/certificates/cacert.pem'),
    'D:/wamp64/cacert.pem',
] as $candidate) {
    if (is_string($candidate) && $candidate !== '' && file_exists($candidate)) {
        $defaultCaBundle = $candidate;
        break;
    }
}

return [
    'name' => 'Aichat',
    'chat' => [
        'enabled' => env('AICHAT_CHAT_ENABLED', true),
        'workflow_profile' => env('AICHAT_CHAT_WORKFLOW_PROFILE', 'root_org_context_v1'),
        'general_first_mode' => filter_var(env('AICHAT_CHAT_GENERAL_FIRST_MODE', true), FILTER_VALIDATE_BOOLEAN),
        'stream_timeout_seconds' => (int) env('AICHAT_CHAT_STREAM_TIMEOUT_SECONDS', 180),
        'request_timeout_seconds' => (int) env('AICHAT_CHAT_REQUEST_TIMEOUT_SECONDS', 120),
        'throttle_per_minute' => (int) env('AICHAT_CHAT_THROTTLE_PER_MINUTE', 30),
        'verify_ssl' => filter_var(env('AICHAT_CHAT_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
        'ca_bundle' => env('AICHAT_CHAT_CA_BUNDLE', $defaultCaBundle),
        'default_provider' => env('AICHAT_CHAT_DEFAULT_PROVIDER', 'openai'),
        'default_model' => env('AICHAT_CHAT_DEFAULT_MODEL', 'gpt-4o-mini'),
        'share_ttl_hours' => (int) env('AICHAT_CHAT_SHARE_TTL_HOURS', 168),
        'retention_days' => (int) env('AICHAT_CHAT_RETENTION_DAYS', 90),
        'idle_timeout_minutes' => (int) env('AICHAT_CHAT_IDLE_TIMEOUT_MINUTES', 30),
        'pii_policy' => env('AICHAT_CHAT_PII_POLICY', 'block'),
        'moderation_enabled' => filter_var(env('AICHAT_CHAT_MODERATION_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'moderation_terms' => array_values(array_filter(array_map('trim', explode(',', (string) env('AICHAT_CHAT_MODERATION_TERMS', ''))))),
        'memory_enabled' => filter_var(env('AICHAT_CHAT_MEMORY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'suggested_replies' => [
            'Summarize the key business points.',
            'Give me concrete next steps.',
            'What data should I review next?',
        ],
        'organization_context' => [
            'products_limit' => (int) env('AICHAT_CHAT_PRODUCTS_CONTEXT_LIMIT', 25),
            'contacts_limit' => (int) env('AICHAT_CHAT_CONTACTS_CONTEXT_LIMIT', 25),
            'transactions_limit' => (int) env('AICHAT_CHAT_TRANSACTIONS_CONTEXT_LIMIT', 20),
        ],
        'pii_replacement_map' => [
            'email' => '[redacted email]',
            'phone' => '[redacted phone]',
            'password' => '[redacted password]',
            'auth' => '[redacted auth secret]',
        ],
        'pii_sensitive_terms' => [
            'email',
            'e-mail',
            'phone',
            'telephone',
            'mobile',
            'password',
            'passwd',
            'pwd',
            'secret',
            'api key',
            'api_key',
            'access token',
            'access_token',
            'refresh token',
            'refresh_token',
            'auth token',
            'auth_token',
            'authorization',
            'bearer',
            'session id',
            'session_id',
        ],
        'providers' => [
            'openai' => [
                'label' => 'OpenAI',
                'base_url' => env('AICHAT_CHAT_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
                'models' => [
                    'gpt-4o' => 'GPT-4o',
                    'gpt-4o-mini' => 'GPT-4o Mini',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                ],
            ],
            'gemini' => [
                'label' => 'Gemini',
                'base_url' => env('AICHAT_CHAT_GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
                'models' => [
                    'gemini-3.1-pro-preview' => 'Gemini 3.1 Pro Preview',
                    'gemini-3-flash-preview' => 'Gemini 3 Flash Preview',
                    'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                    'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite',
                    'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                    'gemini-flash-latest' => 'Gemini Flash Latest',
                ],
            ],
            'openrouter' => [
                'label' => 'OpenRouter',
                'base_url' => env('AICHAT_CHAT_OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
                'models' => [
                    'openrouter/free' => 'OpenRouter Free (auto)',
                    'openrouter/auto' => 'OpenRouter Auto',
                    'meta-llama/llama-3.2-3b-instruct:free' => 'Llama 3.2 3B (free)',
                    'google/gemma-2-9b-it:free' => 'Gemma 2 9B (free)',
                    'qwen/qwen3-4b:free' => 'Qwen3 4B (free)',
                    'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet',
                    'openai/gpt-4o' => 'OpenAI GPT-4o',
                ],
            ],
            'deepseek' => [
                'label' => 'DeepSeek',
                'base_url' => env('AICHAT_CHAT_DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
                'models' => [
                    'deepseek-chat' => 'DeepSeek Chat',
                    'deepseek-coder' => 'DeepSeek Coder',
                ],
            ],
            'groq' => [
                'label' => 'Groq',
                'base_url' => env('AICHAT_CHAT_GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
                'models' => [
                    'llama-3.1-8b-instant' => 'Llama 3.1 8B Instant',
                    'llama-3.1-70b-versatile' => 'Llama 3.1 70B Versatile',
                    'mixtral-8x7b-32768' => 'Mixtral 8x7B',
                ],
            ],
        ],
    ],
    'telegram' => [
        'request_timeout_seconds' => (int) env('AICHAT_TELEGRAM_REQUEST_TIMEOUT_SECONDS', 20),
        'verify_ssl' => filter_var(env('AICHAT_TELEGRAM_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
        'ca_bundle' => env('AICHAT_TELEGRAM_CA_BUNDLE', $defaultCaBundle),
        'webhook_base_url' => trim((string) env('AICHAT_TELEGRAM_WEBHOOK_BASE_URL', '')),
        'webhook_rate_limit_per_minute' => (int) env('AICHAT_TELEGRAM_WEBHOOK_RATE_LIMIT_PER_MINUTE', 60),
        'chat_rate_limit_per_minute' => (int) env('AICHAT_TELEGRAM_CHAT_RATE_LIMIT_PER_MINUTE', 20),
        'business_rate_limit_per_minute' => (int) env('AICHAT_TELEGRAM_BUSINESS_RATE_LIMIT_PER_MINUTE', 100),
    ],
    'quote_wizard' => [
        'enabled' => filter_var(env('AICHAT_QUOTE_WIZARD_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'draft_ttl_hours' => (int) env('AICHAT_QUOTE_WIZARD_DRAFT_TTL_HOURS', 24),
        'max_contact_results' => (int) env('AICHAT_QUOTE_WIZARD_MAX_CONTACT_RESULTS', 8),
        'max_product_results' => (int) env('AICHAT_QUOTE_WIZARD_MAX_PRODUCT_RESULTS', 8),
        'process_throttle_per_minute' => (int) env('AICHAT_QUOTE_WIZARD_PROCESS_THROTTLE_PER_MINUTE', env('AICHAT_CHAT_THROTTLE_PER_MINUTE', 30)),
        'confirm_throttle_per_minute' => (int) env('AICHAT_QUOTE_WIZARD_CONFIRM_THROTTLE_PER_MINUTE', 10),
    ],
];
