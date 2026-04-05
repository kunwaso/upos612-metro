## About Ultimate POS

Ultimate POS is a POS application by [Ultimate Fosters](http://ultimatefosters.com), a brand of [The Web Fosters](http://thewebfosters.com).

## AI Steering Commands (Design & UI)

When working with an AI coding agent (e.g. Cursor, Codex) that uses this repo’s `AGENTS.md` and `ai/ui-components.md`, you can steer design and UI work with the following commands. Type the command (or a short phrase that matches it) in chat; the agent will treat it as the corresponding intent and follow the flow described in `AGENTS-FAST.md` / `AGENTS.md`.

| Command        | What it does                                                                                                                                                                     | How to use                                                                           |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| **/audit**     | Run technical quality checks on a view: accessibility (focus, contrast), responsive structure, asset paths, Metronic compliance.                                                 | e.g. “/audit product edit page” or “audit `resources/views/product/edit.blade.php`”. |
| **/critique**  | UX design review: hierarchy, clarity, emotional resonance, empty and error states.                                                                                               | e.g. “/critique checkout form” or “critique the sales list view”.                    |
| **/normalize** | Align markup and patterns with the project design system (Metronic 8.3.3): correct classes, structure, and references from `public/html/` or `Modules/ProjectX/Resources/html/`. | e.g. “/normalize settings page” or “normalize this modal to Metronic”.               |
| **/polish**    | Final pass before shipping: tighten hierarchy, spacing, and copy within existing Metronic components; no new classes.                                                            | e.g. “/polish dashboard” or “polish the header of the product list”.                 |
| **/distill**   | Strip the UI to its essence: remove redundant wrappers, nested cards, or duplicate structure while keeping behavior and Metronic patterns.                                       | e.g. “/distill product card” or “simplify this section”.                             |
| **/clarify**   | Improve unclear UX copy: button labels, error messages, empty states, placeholders. Use existing translation keys where possible.                                                | e.g. “/clarify form labels” or “clarify error messages on this form”.                |
| **/optimize**  | Performance improvements for the page or component: e.g. asset loading, inline scripts, or DOM structure, within Metronic and Blade.                                             | e.g. “/optimize product index” or “optimize this datatable page”.                    |
| **/harden**    | Harden for production: error handling, i18n, edge cases (empty data, permissions), and validation feedback in the UI.                                                            | e.g. “/harden invoice view” or “harden this modal for edge cases”.                   |
| **/animate**   | Add purposeful, subtle motion (transitions, loading states) using Metronic patterns; respect `prefers-reduced-motion` if custom motion is added.                                 | e.g. “/animate modal open” or “add a subtle loading state here”.                     |
| **/colorize**  | Introduce strategic use of color within the Metronic palette: badges, alerts, status, or emphasis without inventing new classes.                                                 | e.g. “/colorize status badges” or “use color to show priority”.                      |
| **/bolder**    | Amplify a boring or low-contrast design: stronger headings, clearer hierarchy, or more prominent CTAs within Metronic.                                                           | e.g. “/bolder CTA” or “make the main action more prominent”.                         |
| **/quieter**   | Tone down an overly bold or busy design: reduce visual noise, soften emphasis, or simplify layout within Metronic.                                                               | e.g. “/quieter sidebar” or “tone down the dashboard widgets”.                        |
| **/delight**   | Add small moments of joy or clarity: microcopy, success feedback, or a clearer empty state, without changing the theme.                                                          | e.g. “/delight empty state” or “add a friendly message when the list is empty”.      |
| **/extract**   | Pull repeated markup into reusable Blade components or partials that follow Metronic and `ai/ui-components.md`.                                                                  | e.g. “/extract card pattern” or “extract this into a component”.                     |
| **/adapt**     | Adapt layout or components for different viewports or devices using Metronic’s responsive utilities and reference.                                                               | e.g. “/adapt for mobile” or “make this table responsive”.                            |
| **/onboard**   | Design or refine onboarding flows: first-time hints, tooltips, or step-by-step UI using existing patterns and translation keys.                                                  | e.g. “/onboard first login” or “add a short onboarding for this feature”.            |
| **/teach**     | One-time setup: gather design/UI context (e.g. which pages matter, brand constraints) and document it for the agent (e.g. in `ai/` or a short doc).                              | e.g. “/teach design context” or “record our UI preferences for the agent”.           |

All commands are scoped to **Metronic 8.3.3** and project rules: no new theme, no invented CSS classes. See `ai/ui-components.md` for design principles and anti-patterns, and `AGENTS.md` / `AGENTS-FAST.md` for the full intent router.

## Agent Workflow

Use these docs in this order when working with Codex or Cursor in this repo:

1. `AGENTS-FAST.md` for the short lane picker and day-to-day execution defaults
2. `AGENTS.md` for the canonical workflow, tool-depth policy, and five checks
3. `ai/entrypoints/INDEX.md` when the correct root or module entry point is unclear
4. `ai/agent-tools-and-mcp.md` for exact tool choice, MCP fallback, and startup behavior
5. `.cursor/plans/README.md` for phased implementation plans with verification and todo lists

### CMS public storefront catalog

The CMS shop at `/shop/catalog` lists **`App\Product`** rows for the business id set in **`.env`** as **`CMS_STOREFRONT_BUSINESS_ID`** (integer). If unset, the catalog renders empty. After changing `.env`, run `php artisan config:clear` if config is cached.
Storefront checkout/cart pages are replaced by a product-level RFQ flow at `/shop/product/{id}/request-quote`, which stores rows in `cms_quote_requests` and (when the Essentials module and `essentials_to_dos` tables exist) creates an assigned **To Do** for the business. Optional: set **`CMS_STOREFRONT_RFQ_TODO_USER_ID`** to force assignment to a specific user id; otherwise the business **owner** or first business user is used.

README is only the entry point. It should link to the canonical docs above instead of duplicating policy.

## Aichat: Improving the Assistant (Logs + Audit)

Use this workflow whenever users report unhelpful, vague, or wrong Aichat responses. The goal is to correlate engineering-detail logs with DB audit signals, identify the failure type, and apply the right fix.

For the full failure-type-to-file reference, complete audit action catalog, and SQL examples see [`Modules/Aichat/docs/developer-improvement-workflow.md`](Modules/Aichat/docs/developer-improvement-workflow.md).

### Step 1 — Check Laravel logs for exception detail

```bash
# Replace the date with today's or the relevant date
grep -i "Aichat\|message_send\|message_stream" storage/logs/laravel-2026-04-05.log
```

Look for: provider HTTP errors, exception stack traces (class + file + line), `message_send_error`, `message_stream_error`.

### Step 2 — Query the audit DB for signal

```sql
-- Top actions last 7 days (quick triage)
SELECT action, COUNT(*) AS cnt
FROM aichat_chat_audit_logs
WHERE created_at >= NOW() - INTERVAL 7 DAY
GROUP BY action ORDER BY cnt DESC LIMIT 20;

-- Pipeline rejections by error_type and channel
SELECT JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.error_type')) AS error_type,
       JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.channel')) AS channel,
       COUNT(*) AS cnt
FROM aichat_chat_audit_logs
WHERE action = 'chat_message_pipeline_rejected'
  AND created_at >= NOW() - INTERVAL 7 DAY
GROUP BY error_type, channel;
```

Export via artisan (no DB client needed):

```bash
php artisan aichat:audit-export --since="-7 days" --format=json
php artisan aichat:audit-export --business_id=1 --action=chat_message_pipeline_rejected --format=csv
```

### Step 3 — Map signal to code and fix

| Signal | Root cause area | File to open |
|--------|----------------|--------------|
| `chat_message_pipeline_rejected` + `error_type=model_invalid` | Model not in business allowlist | `ChatWorkflowUtil`, `ChatUtil::isModelAllowedForBusiness` |
| `chat_message_pipeline_rejected` + `error_type=pii_blocked` | PII policy rejecting prompt | `ChatWorkflowUtil::applyPiiPolicy`, `aichat_chat_settings.pii_policy` |
| `chat_message_pipeline_rejected` + `error_type=credential_missing` | API key not set for provider | `ChatUtil::resolveCredentialForChat`, `aichat_chat_credentials` |
| `message_send_error` / `message_stream_error` | Provider API failure | `AIChatUtil`, Laravel log for stack trace |
| `chat_action_denied` | Permission gap | `ChatCapabilityResolver`, Spatie permissions |
| `message_feedback_saved` with low thumbs-down ratio | Prompt / context quality | `ChatUtil::buildOrganizationContext`, system prompt in `aichat_chat_settings` |

### Step 4 — Verify the fix

Re-run the same audit query after deploying and confirm the target action count drops. See [`ai/aichat-authz-baseline.md`](ai/aichat-authz-baseline.md) for capability resolver details and [`ai/agent-team-policy.md`](ai/agent-team-policy.md) for approved MCP and PII policy.

## Request A Phased Plan

Use a prompt like this when you want a plan another agent can execute directly:

```text
Build me a detailed implementation plan phase by phase, task by task, with a todo list.
Use `.cursor/plans/README.md` format.
Explain what this plan actually does.
For each phase include:
- goal
- task table with reference and deliverable
- verification
- assumptions and no-change areas
Make sure the plan is decision-complete so the agent can code correctly.
```

Build me a detailed implementation plan phase by phase, task by task, with a todo list. Make sure to use worker agent to coding faster if needed. use metronic ui style template at public\html or web link component https://preview.keenthemes.com/html/metronic/docs/base/utilities.  
ask me any question if need to clarify the plan is decision-complete so the agent can code correctly.

## Common Repo Shortcuts

Useful repo commands that are worth keeping visible:

```bash
php artisan vendor:publish --tag=cms-assets --force
./warm-and-index.bat
./warm-and-index.bat --skip-gitnexus --no-pause

php mcp/semantic-code-search-mcp/bin/index-codebase --force



composer entrypoints:generate
composer entrypoints:check


