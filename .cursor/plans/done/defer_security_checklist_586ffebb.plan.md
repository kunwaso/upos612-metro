---
name: Defer Security Checklist
overview: "Document in the Aichat Telegram Bot Integration plan that the Security checklist (todo #7 and §13 Security items) is deferred to a later deployment, so initial release can ship without it and it can be added in a follow-up."
todos: []
isProject: false
---

# Defer Security Checklist to Later Deployment

## Current state

In [.cursor/plans/aichat_telegram_bot_integration_fb70f60f.plan.md](.cursor/plans/aichat_telegram_bot_integration_fb70f60f.plan.md):

- **§14 Implementation todos** list 7 items; **#7 is "Security checklist"** (max prompt length, never log token, HTTPS doc, PII-safe logging).
- **§13 Improvements** has a **Security** subsection with: webhook secret, token never logged, input max length, PII/logs, HTTPS.

The plan does not currently state that the Security checklist can be shipped in a later phase.

## Change to make (when not in plan mode)

1. **Front-matter todos**
  - Keep the `input-validation` todo (or rename to `security-checklist`) but add a note that it is **deploy later** (e.g. in the todo content or in a short "Phase 2" note in the plan).
2. **§14 Implementation todos**
  - Add an explicit note that **Todo #7 (Security checklist)** is **Phase 2 / deploy later**: implement and ship todos 1–6 first; do Security checklist in a follow-up deployment.
  - Optionally add a "Phase 2" subheading under §14 and move Security checklist there.
3. **§13 Security**
  - Add one sentence at the start of the Security bullet list: e.g. *"These items can be implemented in a later deployment (Phase 2) after core and workflow improvements are live."*

## Resulting deployment order


| Phase                        | Todos                                                                                                    | When              |
| ---------------------------- | -------------------------------------------------------------------------------------------------------- | ----------------- |
| **Phase 1 (initial deploy)** | 1–6: Core, webhook secret, ProcessTelegramWebhookJob, rate limits, reply split/typing, optional commands | Ship first        |
| **Phase 2 (deploy later)**   | 7: Security checklist (max prompt length, never log token, HTTPS doc, PII-safe logging)                  | Follow-up release |


No change to the technical content of the Security checklist—only to **when** it is delivered (later deployment) and to the plan text so that is explicit.