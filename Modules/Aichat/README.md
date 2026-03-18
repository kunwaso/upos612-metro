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
- `AICHAT_QUOTE_WIZARD_ENABLED`
- `AICHAT_QUOTE_WIZARD_DRAFT_TTL_HOURS`
- `AICHAT_QUOTE_WIZARD_MAX_CONTACT_RESULTS`
- `AICHAT_QUOTE_WIZARD_MAX_PRODUCT_RESULTS`
- `AICHAT_QUOTE_WIZARD_PROCESS_THROTTLE_PER_MINUTE`
- `AICHAT_QUOTE_WIZARD_CONFIRM_THROTTLE_PER_MINUTE`

## Quote Assistant

The quote assistant adds a tenant-scoped draft workflow inside Aichat for Product Quotes.

- Permission: `aichat.quote_wizard.use`
- Confirm still requires: `product_quote.create`
- Human confirmation is required before a quote is created
- Drafts are stored in `aichat_product_quote_drafts`
- Draft expiry is enforced lazily on process/confirm using the configured TTL
- Important chat-side actions are audited with `quote_wizard_step_processed` and `quote_created_from_chat`
- Web APIs stay on: `/aichat/chat/conversations/{id}/quote-wizard/*`
- Web route names stay on: `aichat.chat.conversations.quote_wizard.{contacts|locations|products|costing_defaults|process|confirm}`

### Flow

1. User enables Quote Assistant mode in chat
2. User sends natural-language quote instructions
3. Wizard resolves customer, location, products, and missing fields
4. User picks from any clarification chips when needed
5. User confirms the ready draft
6. System creates the quote with existing `QuoteUtil` and returns admin + public URLs

### Telegram Quote Assistant (private chats only)

Telegram wizard execution uses the existing webhook endpoint and linked private chats:

- Webhook ingress: `POST /aichat/telegram/webhook/{webhookKey}`
- Start/resume wizard: `/quote`
- Cancel active draft: `/cancel`
- Pick customer option: `C <n>`
- Pick product option: `P <line> <n>`
- Create quote: `CONFIRM`

The Telegram wizard uses the same server-side draft + confirm-only persistence rule as web. It does not create quotes from model text alone and only persists via `QuoteUtil` after explicit confirm.

### Privacy / PII

- Customer names and quote details may appear in the chat audit trail because the assistant stores the user and assistant messages for reviewability
- Keep provider credentials server-side; the browser only receives route/config metadata

## Publish assets

Run:

```bash
php artisan vendor:publish --tag=aichat-assets --force
```
