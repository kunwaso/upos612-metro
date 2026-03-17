# ProjectX Chat Workflow (Assumed dezai v1)

This document defines the in-repo assumed workflow contract used by ProjectX chat when `projectx.chat.workflow_profile=dezai_assumed_v1`.

## Contract goals

- Keep all existing HTTP endpoints under `projectx/chat/*` unchanged.
- Keep SSE event names unchanged: `start`, `warning`, `chunk`, `done`, `error`.
- Keep current frontend integration points unchanged (chat page and fabric drawer/sidebar).
- Make the assistant general-first by default; apply fabric-specific behavior only when fabric context is active.

## Canonical pipeline (send / stream)

1. Authorize request (`projectx.chat.edit`).
2. Validate request payload (Form Request rules).
3. Policy checks:
   - provider/model allowlist check
   - PII policy check (`warn` or `block`)
4. Resolve fabric context (optional):
   - only when `fabric_insight=1` and valid `fabric_id`
   - apply fabric permission + business scoping
5. Persist user message.
6. Resolve credential for selected provider (user key first, then business key).
7. Build provider messages:
   - system instructions from active profile
   - conversation history
   - optional live fabric context block
   - current prompt
8. Provider call (generate or stream).
9. Moderate assistant text (if enabled).
10. Persist assistant message.
11. Write audit log.
12. Respond:
   - `send`: JSON payload
   - `stream`: SSE (`start|warning|chunk|done|error`)

## Canonical pipeline (regenerate)

1. Authorize request (`projectx.chat.edit`).
2. Validate regenerate payload.
3. Load target assistant message with tenant/user ownership checks.
4. Enforce regenerate constraints (assistant role, latest assistant message only).
5. Resolve source user message.
6. Run policy + optional fabric context steps.
7. Resolve credential.
8. Build provider messages up to source user message.
9. Stream provider response.
10. Moderate response (if enabled).
11. Replace target assistant message content.
12. Write audit log and return SSE response.

## Profile behavior

### `legacy`

Keeps legacy instruction assembly (structured reasoning blocks, evaluation checks, tools block, project reference, legacy response formatting path).

### `dezai_assumed_v1`

- General-first role instruction.
- Friendly opening behavior for normal conversation.
- Fabric-scoped behavior instruction only when fabric context exists.
- Fabric update JSON formatting instruction only when fabric context exists.
- Legacy heavy coding-oriented blocks are not injected by default.

## Fabric update apply security rule

For `POST /projectx/chat/fabrics/{fabric_id}/messages/{message}/apply-updates`:

- Server verifies message ownership and assistant/fabric origin as before.
- When `projectx.chat.enforce_fabric_update_match=true`, server parses `fabric_updates` from assistant message content and requires the client `updates` payload to match exactly:
  - same key set
  - type-normalized value equality
- Tampered/mismatched payloads are rejected with validation-style error response (HTTP 422).

## Rollout / rollback

Use env flag `PROJECTX_CHAT_WORKFLOW_PROFILE`:

- `dezai_assumed_v1` (default)
- `legacy` (instant fallback)

Related flags:

- `PROJECTX_CHAT_GENERAL_FIRST_MODE=true|false`
- `PROJECTX_CHAT_ENFORCE_FABRIC_UPDATE_MATCH=true|false`
