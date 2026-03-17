# Product Copilot Patterns

Read this when evaluating an **in-app** assistant, browser agent, smart form filler, guided navigation helper, or ERP copilot.

This document is about product features for UPOS users. It is **not** the base workflow for the coding agent itself.

---

## 1. What Belongs Here

Use this guide for ideas such as:

- natural-language help inside a page
- guided multi-step data entry
- page-level assistant panels
- UI navigation helpers
- safe action suggestions with human approval

If the task is about coding-agent workflow, use `ai/external-adoption.md` and `ai/research-and-delegation.md` instead.

---

## 2. Non-Negotiable Safety Rules

Any product copilot design must define:

1. **Tenant scope** — the assistant can only see or act within the current `business_id`
2. **Auth boundary** — the current user and role still govern every action
3. **Permission boundary** — the assistant cannot bypass `auth()->user()->can(...)`
4. **PII boundary** — prompts, context, and logs must respect the project's AI/privacy policy
5. **Human approval** — state-changing actions require an explicit confirm step
6. **Audit trail** — important assistant actions should be attributable and reviewable

If any of these are undefined, the idea is not ready for implementation.

---

## 3. Landing Rules for This Repo

- Prefer a **module-local** landing point over global root behavior.
- Reuse existing tenant-scoped AI patterns before creating parallel systems.
- Use current UI rules from `ai/ui-components.md`; the assistant UI must still fit Metronic 8.3.3.
- Do not inject third-party scripts globally as a first step.
- Keep secrets and provider keys server-side; do not expose privileged runtime config in Blade.

For this repo, start by reviewing `Modules/Aichat/README.md` before proposing a separate assistant architecture.

---

## 4. Safe First Use Cases

Safe first-use candidates:

1. Smart form filling inside one module for one role
2. Guided page navigation or “show me where to click” assistance
3. Accessibility-style help that does not change data
4. Suggestion-only side panel that prepares actions but does not execute them

Avoid these as a first rollout:

1. Cross-module autonomous workflows
2. Background actions with no approval step
3. Global assistant injection across all pages
4. Anything that can mutate data without clear user confirmation

---

## 5. Evaluation Checklist

Before recommending a product copilot, answer:

1. Which module owns the first rollout?
2. Which role is allowed to use it?
3. What exact actions are allowed?
4. Which actions are suggestion-only versus approval-required?
5. What user-visible audit or history exists?
6. What happens when the assistant is wrong or the provider is unavailable?

If those questions are not answered, keep the result at `adapt later`, not `implement now`.

---

## 6. Required Output Format

Every product-copilot evaluation should end with:

1. first module and first role
2. one safe first use case
3. approval boundary
4. tenant/auth/PII notes
5. implementation surface and rollback note
