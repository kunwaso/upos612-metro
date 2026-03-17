---
name: deep-research
description: Run bounded, evidence-first deep research for complex bugs, external-repo intake, or architecture comparisons. Use when a task benefits from parallel fact gathering and synthesis instead of a single straight-line search.
---

# Deep Research

Use this skill for:

- long or ambiguous investigations
- external-repo comparisons
- architecture trade-off analysis
- module audits with many moving parts

## Core Rules

- Split the work into bounded branches.
- Keep facts separate from recommendations.
- Synthesize back into one short, repo-specific answer.
- Fall back quickly if a preferred search/read tool is degraded.

## Branch Pattern

Default branch split:

1. **Upstream facts** — README, manifests, license, examples, releases
2. **Local fit** — repo conventions, landing points, security, UI, tenancy
3. **Synthesis** — adopt/adapt/reject or root-cause/fix recommendation

## Startup Order

1. Read `ai/research-and-delegation.md`.
2. Confirm live checkout shape with `project_map` or filesystem.
3. Read `resource://composer` when dependencies may change.
4. Read the nearest `ai/*.md` domain docs.
5. Only then branch into deeper research.

## Required Output

1. Decision or diagnosis
2. Evidence from each branch
3. Landing path or fix path
4. Verification
5. Remaining uncertainty, if any

## Do Not

- Do not let every branch answer the same question.
- Do not turn bounded research into endless exploration.
- Do not hide uncertainty; name it explicitly when evidence is incomplete.
