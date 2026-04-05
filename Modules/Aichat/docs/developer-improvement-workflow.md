# Aichat Developer Improvement Workflow

**Owner:** UPOS Engineering  
**Entry point:** [`readme.md`](../../../readme.md) — "Aichat: Improving the Assistant" section  
**Last updated:** 2026-04-05

This document provides the detailed reference for improving Aichat response quality after analysing Laravel logs and the `aichat_chat_audit_logs` DB table. Start at `readme.md` for the quick workflow, then use this file for signal-to-fix depth.

---

## 1. Two signal sources and when to use each

| Source | Use for | How to access |
|--------|---------|---------------|
| `storage/logs/laravel-YYYY-MM-DD.log` | Engineering detail: exception class, file, line, provider HTTP status, stack traces | Log file grep / `php artisan tail` |
| `aichat_chat_audit_logs` DB table | Product signals: most frequent failure types, per-tenant trends, action funnel | SQL queries / `php artisan aichat:audit-export` |

Use logs to diagnose one incident; use DB to find what to fix next (volume-driven prioritisation).

---

## 2. Laravel log grep hints

```bash
# All Aichat-related lines
grep -i "Aichat\|aichat" storage/logs/laravel-2026-04-05.log

# Provider API errors only
grep -i "message_send_error\|message_stream_error\|generateText\|streamText" storage/logs/laravel-2026-04-05.log

# Exception stack traces
grep -A 10 "local\.ERROR" storage/logs/laravel-2026-04-05.log | grep -A 10 "Aichat"
```

Correlate log lines with DB audit rows using `conversation_id` (UUID in both log context and `aichat_chat_audit_logs.conversation_id`).

---

## 3. Audit table schema quick reference

Table: `aichat_chat_audit_logs`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `business_id` | int | Tenant scope |
| `user_id` | int (nullable) | Null for Telegram anonymous paths |
| `conversation_id` | uuid (nullable) | Join to `aichat_chat_conversations` |
| `action` | varchar(100) | See action catalog below |
| `provider` | varchar(32) | e.g. `openai`, `gemini`, `groq` |
| `model` | varchar(120) | e.g. `gpt-4o-mini` |
| `metadata` | JSON | Redacted; safe diagnostic fields only |
| `created_at` | timestamp | Indexed with `action` and `business_id` |

Key indexes: `(business_id, created_at)`, `(action, created_at)`, `(conversation_id, created_at)`.

---

## 4. Complete audit action catalog

Actions written by `ChatUtil::audit()` → `ChatAuditUtil::log()` throughout the module.

### Chat conversation lifecycle

| Action | Written in | When |
|--------|-----------|------|
| `conversation_created` | `ChatController::create` | New conversation started |
| `conversation_deleted` | `ChatController::delete` | Conversation deleted by user |
| `conversation_share_link_created` | `ChatController::share` | Share link generated |
| `conversation_export` | `ChatController::export` | Export requested; `metadata.format` = `markdown`/`pdf` |

### Message send / stream pipeline

| Action | Written in | When |
|--------|-----------|------|
| `chat_message_pipeline_rejected` | `ChatController::send` / `stream` / `regenerate`, `ProcessTelegramWebhookJob` | `prepareSendOrStreamContext` returned `success: false`; `metadata.error_type` = see below; `metadata.channel` = `web`/`telegram` |
| `message_send_success` | `ChatController::send` | Non-streaming send completed; `metadata.warning_count` |
| `message_send_error` | `ChatController::send` | Provider threw exception; `metadata.error` = exception message |
| `message_stream_success` | `ChatController::stream` | SSE stream completed; `metadata.response_chars` |
| `message_stream_error` | `ChatController::stream` | Provider threw during streaming; `metadata.error` |
| `message_regenerated` | `ChatController::regenerate` | Regenerate stream completed |
| `message_regenerate_error` | `ChatController::regenerate` | Provider threw during regenerate |
| `message_feedback_saved` | `ChatController::feedback` | Thumbs up/down saved; `metadata.feedback` = `thumbs_up`/`thumbs_down` |

### Pipeline rejection `error_type` values (from `ChatWorkflowUtil`)

| `error_type` | Meaning | Fix path |
|-------------|---------|----------|
| `model_invalid` | Requested model not in business allowlist | `ChatUtil::isModelAllowedForBusiness`, `aichat_chat_settings.model_allowlist` |
| `pii_blocked` | PII policy (`block`) rejected the prompt | `ChatWorkflowUtil::applyPiiPolicy`, `aichat_chat_settings.pii_policy`, `ChatSensitiveDataRedactor` |
| `credential_missing` | No API key for provider | `ChatUtil::resolveCredentialForChat`, `aichat_chat_credentials` table |

### Actions actions

| Action | Written in | When |
|--------|-----------|------|
| `chat_action_prepared` | `ChatActionUtil::prepare` | Pending action created |
| `chat_action_confirmed` | `ChatActionUtil::confirm` | Action confirmed by user |
| `chat_action_cancelled` | `ChatActionUtil::cancel` | Action cancelled |
| `chat_action_executed` | `ChatActionUtil::execute` | Action executed successfully |
| `chat_action_failed` | `ChatActionUtil::execute` | Action execution threw |
| `chat_action_expired` | `ChatActionUtil` | TTL exceeded before confirm/cancel |
| `chat_action_denied` | `ChatActionController` | Permission check failed; `metadata.reason` |

### Quote wizard

| Action | Written in | When |
|--------|-----------|------|
| `quote_wizard_step_processed` | `ChatQuoteWizardController`, `ProcessTelegramWebhookJob` | Wizard step resolved |
| `quote_wizard_cancelled` | `ProcessTelegramWebhookJob` | Draft cancelled via `/cancel` |
| `quote_created_from_chat` | `ChatQuoteWizardController`, `ProcessTelegramWebhookJob` | Quote persisted after confirm |

### Settings

| Action | Written in | When |
|--------|-----------|------|
| `business_settings_updated` | `ChatSettingsController` | Business chat settings changed |
| `user_chat_profile_updated` | `ChatSettingsController` | User profile updated |
| `memory_created` / `memory_updated` / `memory_deleted` | `ChatSettingsController` | Memory item CRUD |
| `telegram_bot_saved` / `telegram_bot_deleted` | `ChatSettingsController` | Telegram bot config |
| `telegram_allowed_users_updated` | `ChatSettingsController` | Allowed users list changed |
| `telegram_allowed_group_added` / `_removed` | `ChatSettingsController` | Group allow-list |
| `persistent_memory_display_name_updated` / `persistent_memory_wiped` | `ChatMemoryAdminController` | Superadmin memory ops |

---

## 5. SQL queries for common improvement tasks

### Top 20 actions last 7 days (triage)

```sql
SELECT action, COUNT(*) AS cnt
FROM aichat_chat_audit_logs
WHERE created_at >= NOW() - INTERVAL 7 DAY
GROUP BY action
ORDER BY cnt DESC
LIMIT 20;
```

### Pipeline rejections by error_type and channel

```sql
SELECT JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.error_type')) AS error_type,
       JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.channel'))    AS channel,
       COUNT(*)                                             AS cnt
FROM aichat_chat_audit_logs
WHERE action = 'chat_message_pipeline_rejected'
  AND created_at >= NOW() - INTERVAL 30 DAY
GROUP BY error_type, channel
ORDER BY cnt DESC;
```

### Feedback ratio (quality signal)

```sql
SELECT JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.feedback')) AS feedback,
       COUNT(*) AS cnt
FROM aichat_chat_audit_logs
WHERE action = 'message_feedback_saved'
  AND created_at >= NOW() - INTERVAL 30 DAY
GROUP BY feedback;
```

### Provider error rate

```sql
SELECT provider,
       model,
       SUM(CASE WHEN action IN ('message_send_error','message_stream_error','message_regenerate_error') THEN 1 ELSE 0 END) AS errors,
       SUM(CASE WHEN action IN ('message_send_success','message_stream_success','message_regenerated') THEN 1 ELSE 0 END) AS successes
FROM aichat_chat_audit_logs
WHERE created_at >= NOW() - INTERVAL 7 DAY
  AND action IN ('message_send_error','message_stream_error','message_regenerate_error',
                 'message_send_success','message_stream_success','message_regenerated')
GROUP BY provider, model
ORDER BY errors DESC;
```

### Scoped to one tenant

Append `AND business_id = ?` to any query above.

---

## 6. Artisan export (no DB client)

```bash
# JSON, all actions, last 7 days
php artisan aichat:audit-export --since="-7 days" --format=json

# CSV for one business, pipeline rejections only
php artisan aichat:audit-export --business_id=1 --action=chat_message_pipeline_rejected --format=csv

# Write to file instead of stdout
php artisan aichat:audit-export --since="-30 days" --format=csv --output=storage/app/aichat-audit.csv

# Limit rows (useful for quick spot-check)
php artisan aichat:audit-export --limit=100 --format=json
```

---

## 7. Improvement loop

1. Run triage query (section 5, query 1) — pick the top failure action.
2. Drill down with the relevant query or artisan export.
3. Identify the root cause using the "error_type → fix path" table (section 4).
4. Apply the minimal fix in the correct layer (capability map, prompt, tool, permission, settings).
5. Deploy and re-run the same query — confirm the count drops.
6. If the fix adds a new gap or uncovers a new pattern, open a ticket or add an entry to [`ai/known-issues.md`](../../../ai/known-issues.md).

---

## 8. Key files for common fixes

| What to change | File |
|---------------|------|
| System prompt / reasoning rules | `aichat_chat_settings` DB row or `ChatUtil::buildOrganizationContext` |
| Capability map (what domains are reachable) | `Modules/Aichat/Utils/ChatCapabilityResolver.php` |
| PII policy / redaction patterns | `Modules/Aichat/Config/config.php` `security.redaction`, `ChatSensitiveDataRedactor` |
| Model allowlist | `aichat_chat_settings.model_allowlist` or `AICHAT_CHAT_MODEL_ALLOWLIST` env |
| API credential resolution | `ChatUtil::resolveCredentialForChat`, `aichat_chat_credentials` |
| Action permission gating | `ChatCapabilityResolver`, Spatie permissions, `AICHAT_ACTIONS_*` env flags |
| Context builders (products, contacts, sales…) | `ChatUtil::buildProductsContext`, `buildContactsContext`, `buildSalesContext`, etc. |
