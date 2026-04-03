# Improving Agent Workflow (What Works in This Repo)

This document explains how to make Codex and Cursor agents **faster and more consistent** using only this repo (`ai/` + AGENTS.md). It also clarifies which ML/training techniques **cannot** be applied here.

---

## 1. What You Cannot Do in This Repo

These techniques require **model training, fine-tuning, or platform-level inference control**. They are not something you implement in `ai/` or AGENTS.md; Cursor uses pre-built models and controls inference.

| Technique | What it is | Why it's not in-repo |
|-----------|------------|----------------------|
| **Reward models** | A model trained to score outputs (e.g. good vs bad code). | Training happens in ML pipelines; you don't train models inside the project. |
| **DPO (Direct Preference Optimization)** | Training the LLM on preference pairs (chosen vs rejected). | Requires training runs and updated model weights; not configurable in docs. |
| **RLHF-style pipelines** | Human feedback → reward model → policy update. | Same as above; no training pipeline in the repo. |
| **Parameter pruning** | Removing weights to shrink the model. | Model architecture; done by the model provider. |
| **Low-rank adaptation (LoRA)** | Small adapter layers for fine-tuning. | Fine-tuning; requires training infrastructure. |
| **Knowledge distillation (KL)** | Training a smaller model to mimic a larger one. | Training-time loss; not something you set in `ai/`. |
| **Temperature scaling** | Inference parameter controlling randomness. | Set in the **platform** (Cursor settings or API), not in project files. |

**Bottom line:** You cannot run DPO, RLHF, LoRA, or distillation from AGENTS.md or `ai/`. To improve the agent with those, you'd need your own training pipeline or platform features (e.g. Cursor exposing temperature).

---

## 2. What You Can Do in This Repo (Faster, More Consistent Workflow)

You **can** approximate the *goals* of “better behavior” and “faster convergence” by **capturing reasoning**, **documenting preferences**, and **distilling knowledge** into `ai/` and AGENTS.md. The model then reads these at runtime — no training.

### 2.1 Capture reasoning

- **Idea:** When a fix or design decision is non-obvious, document *how* it was reasoned (not just the outcome).
- **Where:** Add a short “Reasoning” or “How we diagnosed” section in `ai/known-issues.md` for that area, or create `ai/reasoning-patterns.md` for cross-cutting patterns.
- **Example:** “For ‘button stops working after tab switch,’ we always: grep for the button ID → find handler → find where the node is rendered → check if that DOM is replaced (tabs/AJAX) → fix via event delegation or re-bind. See AGENTS.md 0.4a/0.4b.”
- **Effect:** The agent reuses the same reasoning path instead of re-deriving it → **faster** and more consistent.

### 2.2 Document preferences (reward-like signal)

- **Idea:** Encode “we prefer X, avoid Y” so the agent behaves as if it had been trained on preferences — without a reward model.
- **Where:** In `ai/laravel-conventions.md`, `ai/ui-components.md`, or a dedicated `ai/preferences.md`: short “Preferred” / “Avoid” lists per domain.
- **Example:** “Preferred: one controller method per endpoint; avoid: adding more than 20 lines to HomeController.” Or: “Preferred: Metronic classes from ui-components.md; avoid: any new custom utility class.”
- **Effect:** Fewer bad patterns and rework → **faster** iteration and fewer five-check failures.

### 2.3 Distill knowledge into `ai/`

- **Idea:** When something is learned (from debugging, refactors, or reviews), add it to the right `ai/` doc so the next run doesn’t reason from scratch.
- **Where:** `ai/known-issues.md` for traps; `ai/database-map.md` for schema/relationships; `ai/ui-components.md` for UI; `ai/laravel-conventions.md` for structure.
- **Effect:** More answers come from “read ai/” instead of long search chains → **faster** and more accurate.

### 2.4 Keep AGENTS.md process tight

- **Idea:** Clear steps (0.2, 0.2a, 0.4a/0.4b, 0.5, five checks) reduce wandering and rework.
- **Already in place:** Design → plan → execute, TDD, verification before completion, mandatory lints.
- **Optional:** Add one-line “read ai/agent-improvement.md when considering workflow changes.”

### 2.5 Platform tools and MCP

- **Idea:** Speed and consistency also improve when the agent uses the right platform tools, not just better written instructions.
- **Use cases:** Fetch URL for live docs, subagents for parallel exploration or shell work, and the Laravel MySQL MCP server for routes, schema, migrations, and tests when it is enabled in the environment.
- **Why this is still in-repo:** The repo can document which tools to prefer and when to reach for them. No model training is required.
- **Read next:** `ai/agent-tools-and-mcp.md` is the reference for tool families, speed-oriented tool choice, and Laravel MCP setup for Codex and Cursor.

### 2.6 Minimize tool calls (faster execution)

- **Idea:** Total execution time is roughly **(number of tool calls) × (round-trip + MCP overhead)**. More, smaller steps → more time even when the total code change is small. Improve the agent’s logic so it does **fewer, smarter** steps.
- **Preferred:**
  - **Plan first:** For multi-file work, list all target files and directories from the plan (or a quick glob/grep) before editing. Create any missing directories in one go at the start.
  - **One Write per file:** When creating or heavily changing a file, prefer a single Write (full file) over many small Edit/Replace calls. Use Search-replace only when the change is small and localized.
  - **Strict plan-then-execute:** Follow AGENTS.md 0.5: design → plan (with file list + dirs) → execute. Do not “discover as you go” (e.g. finding “directory missing” mid-task).
- **Avoid:**
  - Dozens of small Edit File calls when one Write per file would suffice.
  - Discovering “directory doesn’t exist” mid-task — check or create required dirs at the start.
  - Starting edits before the full file/directory list is known.
- **Plans:** When writing a plan (e.g. in `.cursor/plans/` or AGENTS.md), include a **“Directories to create (if missing)”** line and a **file list summary** so the agent doesn’t have to infer or discover layout mid-run.
- **Effect:** Fewer round-trips and less backtracking → faster runs. See `ai/agent-tools-and-mcp.md` §2.7 for edit/write preferences.

### 2.7 Canonical ownership and overlap cleanup

Use one primary document per rule family so improvements do not create three competing summaries:

| Rule family | Primary home | Common overlap to avoid |
|------|------------------|-------------|
| Policy, lanes, five checks, review gate | `AGENTS.md` | Repeating full policy in README or tool docs |
| Fast execution defaults | `AGENTS-FAST.md` | Repeating detailed edge cases that belong in `AGENTS.md` |
| Tool selection and fallback | `ai/agent-tools-and-mcp.md` | Embedding full MCP routing tables in README |
| Plan structure and verification | `.cursor/plans/README.md` | Ad hoc phased-plan formats in prompts or scratch docs |
| User-facing examples and shortcuts | `readme.md` | Turning README into a second policy source |

Overlap map for this repo:

- `AGENTS.md` and `AGENTS-FAST.md` both discuss lanes; keep the detailed logic in `AGENTS.md` and the short default path in `AGENTS-FAST.md`.
- `AGENTS.md` and `ai/agent-tools-and-mcp.md` both mention tools; keep policy in `AGENTS.md`, but keep the exact tool contract and degraded-tool behavior in `ai/agent-tools-and-mcp.md`.
- `readme.md` may keep shortcuts and examples, but should link back to canonical docs instead of storing scratch policy.
- `.cursor/plans/README.md` owns phased-plan shape; when a plan request appears elsewhere, point back here instead of inventing a new structure.

If you improve one workflow rule, update the primary home first, then trim or relink any overlapping summary.

### 2.8 Session memory vs repo truth (knowledge tiers)

Keep a clear boundary between ephemeral session context and durable repo knowledge:

| Tier | Where | Lifespan | Examples |
|------|-------|----------|----------|
| **Session scratch** | Agent's working memory, `.cursor/plans/*.plan.md` | One task or session | Current investigation state, partial findings, intermediate grep results |
| **Durable knowledge** | `ai/*.md`, `ai/known-issues.md`, `ai/entrypoints/*.md` | Permanent (reviewed) | Diagnosed root causes, new conventions, module traps, schema notes |
| **Repo code** | Source files, migrations, tests | Permanent (versioned) | The actual fix, feature, or test |

Rules:
- Plans (`.cursor/plans/`) are **ephemeral** — do not treat plan-file notes as lasting project truth.
- If a session reveals a new trap, convention, or root-cause pattern that would help future sessions, **promote it** to the appropriate `ai/` doc (see §2.9).
- Do not let durable docs accumulate stale session artifacts (e.g. "we tried X and it didn't work" with no resolution).

### 2.9 Promoting session learnings to durable knowledge

When a session produces a reusable insight, promote it so the next agent (or the same agent in a new session) does not re-derive it:

**When to promote:**
- A non-obvious bug diagnosis that took multiple investigation steps
- A new "avoid this pattern" rule discovered during implementation
- A module-specific trap not yet in `ai/known-issues.md`
- A convention decision made during a task (e.g. "we chose X over Y because Z")

**Where to promote:**

| Insight type | Target doc |
|-------------|-----------|
| Bug trap, anti-pattern, or gotcha | `ai/known-issues.md` (add under the relevant section) |
| Schema or relationship discovery | `ai/database-map.md` |
| UI pattern or component note | `ai/ui-components.md` |
| Convention or code-style decision | `ai/laravel-conventions.md` |
| Module entry-point or wiring note | `ai/entrypoints/module-<Name>.md` |
| Workflow or agent behavior improvement | `ai/agent-improvement.md` (this file) |
| Security or auth discovery | `ai/security-and-auth.md` |

**How to promote:**
1. State the finding in one or two sentences.
2. Include the evidence (file path, line, or grep pattern that reveals it).
3. Place it in the correct section of the target doc.
4. If fixing a documented known issue, update or remove that entry in `ai/known-issues.md`.

**When not to promote:**
- Temporary investigation state ("we grepped for X and found 3 files") — this is session scratch.
- Findings specific to one user's local environment (e.g. "PHP 8.0 on Ray's laptop doesn't support X").
- Speculative ideas that were not validated.

---

## 3. Summary

| Goal | In-repo approach | Not in-repo |
|------|------------------|-------------|
| **Faster workflow** | Capture reasoning in `ai/`, document preferences, distill solutions into `ai/`, use the right platform tools and MCP, **minimize tool calls** (plan first, one Write per file, dirs up front), **read-then-write batching** (§2.9 in agent-tools-and-mcp.md), keep AGENTS.md process strict. | DPO, RLHF, reward models, LoRA, pruning, KL distillation. |
| **More consistent behavior** | Same: preferences + reasoning patterns in `ai/`; five checks and verification in AGENTS.md; **session-to-durable promotion** (§2.9 above). | Temperature (set in the client/platform if available). |
| **Knowledge compounding** | Separate session scratch from durable knowledge (§2.8); promote reusable insights to the right `ai/` doc (§2.9); keep `.cursor/plans/` ephemeral. | Persistent agent memory across sessions (requires platform support). |

Use `ai/` to **capture reasoning**, **document preferences**, **distill knowledge**, **minimize tool calls**, **promote session learnings**, and **document tool choice**. Read `ai/agent-tools-and-mcp.md` for the full tool list and when to use Fetch URL, subagents, and MCP for faster answers and implementation.
