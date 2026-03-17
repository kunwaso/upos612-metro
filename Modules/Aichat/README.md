# Aichat

Tenant-scoped AI chat for root/core UPOS screens (no fabric/trim/quote context).

## Required env vars

- `AICHAT_CHAT_ENABLED`
- `AICHAT_CHAT_PII_POLICY` (`block`, `warn`, `off`) - default is `block`
- `AICHAT_CHAT_DEFAULT_PROVIDER`
- `AICHAT_CHAT_DEFAULT_MODEL`
- `AICHAT_CHAT_THROTTLE_PER_MINUTE`
- `AICHAT_CHAT_SHARE_TTL_HOURS`
- `AICHAT_CHAT_OPENAI_BASE_URL`
- `AICHAT_CHAT_GEMINI_BASE_URL`
- `AICHAT_CHAT_OPENROUTER_BASE_URL`
- `AICHAT_CHAT_DEEPSEEK_BASE_URL`
- `AICHAT_CHAT_GROQ_BASE_URL`

## Publish assets

Run:

```bash
php artisan vendor:publish --tag=aichat-assets --force
```
