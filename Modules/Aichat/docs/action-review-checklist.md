# AI Chat Action Review Checklist

Use this checklist before enabling `AICHAT_ACTIONS_ENABLED=true` in production.

## 1) Permission gates

- [ ] Capability map (`ChatCapabilityResolver`) uses existing app permissions only.
- [ ] Each action in `ChatActionUtil` checks module/action capability before prepare and confirm.
- [ ] `*_own` permissions are used only for read scope, not to grant mutation permissions.
- [ ] Unsupported actions return a standardized forbidden/unsupported response.

## 2) Tenant and ownership safety

- [ ] Every entity lookup in action handlers scopes by `business_id`.
- [ ] Conversation and pending action access is scoped by `business_id + user_id + conversation_id`.
- [ ] Cross-tenant IDs are rejected by handler queries (`firstOrFail`/permission checks).

## 3) Confirmation policy

- [ ] Mutation actions are never executed during prepare.
- [ ] Confirm endpoint is required for mutation execution.
- [ ] Pending action status transitions are valid (`pending -> confirmed -> executed`, with cancel/expire/fail guards).
- [ ] Duplicate confirm is idempotent and does not execute twice.

## 4) Channel parity

- [ ] Web endpoints enforce `aichat.actions.enabled`.
- [ ] Telegram action commands (`/actions`, `/confirm_action`, `/cancel_action`) enforce `aichat.actions.enabled`.
- [ ] Channel mismatch cannot bypass confirmation flow.

## 5) Auditing

- [ ] Audit events exist for prepared, confirmed, executed, denied, expired, failed.
- [ ] Audit payload includes action id/module/action and safe context fields.

## 6) Regression commands

Run these before merge:

```bash
php -l Modules/Aichat/Utils/ChatActionUtil.php
php vendor/bin/phpunit Modules/Aichat/Tests/Feature/ChatActionControllerTest.php
php vendor/bin/phpunit Modules/Aichat/Tests/Unit/ProcessTelegramWebhookJobTest.php
php vendor/bin/phpunit Modules/Aichat/Tests/Unit/ChatActionUtilPermissionTest.php
php vendor/bin/phpunit Modules/Aichat/Tests
```

## 7) Rollout gates

- [ ] Keep `AICHAT_ACTIONS_ENABLED=false` in production during deploy.
- [ ] Validate action flows in staging with non-admin and admin roles.
- [ ] Enable production in low traffic window and monitor `chat_action_*` audit events.
- [ ] Roll back by setting `AICHAT_ACTIONS_ENABLED=false`.
