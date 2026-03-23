# Aichat Authorization Baseline (2026-03-23)

## Entry Points and Call Chains
- Web chat send/stream/regenerate:
  - `Modules/Aichat/Http/Controllers/ChatController.php`
  - `Modules/Aichat/Utils/ChatWorkflowUtil.php`
  - `Modules/Aichat/Utils/ChatUtil.php` (`buildProviderMessages`, organization context builders)
- Chat actions (prepare/confirm/cancel/pending):
  - `Modules/Aichat/Http/Controllers/ChatActionController.php`
  - `Modules/Aichat/Utils/ChatActionUtil.php`
- Quote wizard (web + Telegram):
  - `Modules/Aichat/Http/Controllers/ChatQuoteWizardController.php`
  - `Modules/Aichat/Utils/ChatProductQuoteWizardUtil.php`
- Telegram ingress and processing:
  - `Modules/Aichat/Http/Controllers/TelegramWebhookController.php`
  - `Modules/Aichat/Jobs/ProcessTelegramWebhookJob.php`

## Capability Resolver Usage
- Canonical resolver: `Modules/Aichat/Utils/ChatCapabilityResolver.php`
- Main capability consumption:
  - `ChatUtil::resolveCapabilityEnvelope` and `resolveChatCapabilities`
  - `ChatWorkflowUtil` send/regenerate context build
  - `ChatActionUtil` prepare/confirm/report paths
  - `ChatQuoteWizardController` and `ChatProductQuoteWizardUtil`

## Enforced Gaps Closed in This Baseline
- Added tenant-safe actor resolution for capability lookup (no unscoped `User::find` path for capability resolution).
- Added tenant-safe Telegram user resolution by business.
- Added product permission gating to quote wizard product search.
- Replaced auth-session-only own-contact filtering with user-id based policy scope so Telegram paths enforce the same own-scope rules.
- Added central metadata redaction for chat audit logs.
- Added strict serializer/redactor pipeline for model/tool/pending-action payloads.

## Domains and Entities Currently Reachable by Chat
- Products, contacts (customer/supplier), sales, purchases, quotes, reports, settings, chat controls.
- Quote wizard entities:
  - contacts, products, locations, quote draft payload, quote create context.
- Action entities:
  - products, contacts, settings, sales transactions, purchase transactions, quotes, report summaries.
- Extended domain map support exists for module domains such as storage/HRM via config.

## Remaining Watch List
- Keep resolver domain-map config synchronized with installed modules and permission names.
- Keep serializer allowlists updated whenever new chat-facing payload structures are introduced.
- Any new chat retrieval/action path must use shared policy scopes before DB access.
