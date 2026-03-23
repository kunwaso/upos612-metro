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
- `AICHAT_ACTIONS_ENABLED`
- `AICHAT_ACTIONS_CONFIRMATION_TTL_MINUTES`
- `AICHAT_ACTIONS_PRODUCTS_ENABLED`
- `AICHAT_ACTIONS_CONTACTS_ENABLED`
- `AICHAT_ACTIONS_SETTINGS_ENABLED`
- `AICHAT_ACTIONS_SALES_ENABLED`
- `AICHAT_ACTIONS_QUOTES_ENABLED`
- `AICHAT_ACTIONS_PURCHASES_ENABLED`
- `AICHAT_ACTIONS_REPORTS_ENABLED`
- `AICHAT_QUOTE_WIZARD_ENABLED`
- `AICHAT_QUOTE_WIZARD_DRAFT_TTL_HOURS`
- `AICHAT_QUOTE_WIZARD_MAX_CONTACT_RESULTS`
- `AICHAT_QUOTE_WIZARD_MAX_PRODUCT_RESULTS`
- `AICHAT_QUOTE_WIZARD_PROCESS_THROTTLE_PER_MINUTE`
- `AICHAT_QUOTE_WIZARD_CONFIRM_THROTTLE_PER_MINUTE`

## Action Rollout Controls

Action execution (prepare/confirm/cancel/pending) is controlled by a hard feature flag:

- `AICHAT_ACTIONS_ENABLED=false` means all action endpoints are disabled.
- Web chat hides action UI when disabled.
- Telegram `/actions`, `/confirm_action`, and `/cancel_action` return a disabled message when disabled.

Per-module toggles:

- `AICHAT_ACTIONS_PRODUCTS_ENABLED`
- `AICHAT_ACTIONS_CONTACTS_ENABLED`
- `AICHAT_ACTIONS_SETTINGS_ENABLED`
- `AICHAT_ACTIONS_SALES_ENABLED`
- `AICHAT_ACTIONS_QUOTES_ENABLED`
- `AICHAT_ACTIONS_PURCHASES_ENABLED`
- `AICHAT_ACTIONS_REPORTS_ENABLED`

Recommended staged rollout:

1. Keep `AICHAT_ACTIONS_ENABLED=false` in production while deploying code + migration.
2. Enable in staging with limited roles and verify prepare/confirm/cancel flows.
3. Enable production in low-traffic window and monitor `chat_action_*` audit events.
4. Roll back instantly by setting `AICHAT_ACTIONS_ENABLED=false`.

Reviewer + security gate checklist:

- `Modules/Aichat/docs/action-review-checklist.md`

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

### Telegram Action Commands

For pending action confirmation flow:

- List pending actions: `/actions`
- Confirm latest pending action: `/confirm_action`
- Confirm specific action id: `/confirm_action <id>`
- Cancel latest pending action: `/cancel_action`
- Cancel specific action id: `/cancel_action <id>`

### Privacy / PII

- Customer names and quote details may appear in the chat audit trail because the assistant stores the user and assistant messages for reviewability
- Keep provider credentials server-side; the browser only receives route/config metadata

## Publish assets

Run:

```bash
php artisan vendor:publish --tag=aichat-assets --force
```
