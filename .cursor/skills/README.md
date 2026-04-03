# Skills Index

Repo-local reusable workflow skills for Cursor and Codex agents.

**Rule:** Before planning a non-trivial task, scan this index. If a skill matches, read and follow its `SKILL.md` instead of improvising the same workflow from scratch.

---

## Skill Directory

| Skill | Path | Triggers (use when) | Do not use when |
|-------|------|---------------------|-----------------|
| **Cursor Tool Behavior** | `cursor-tool-behavior/SKILL.md` | Agent needs to choose between read_file, grep, semantic search, MCP tools, or terminal commands; avoiding shell file reads or repeated find_symbols calls | The task is pure reasoning with no codebase interaction |
| **Senior Laravel Developer** | `senior-laravel-developer/SKILL.md` | Architecture review, performance audit, security check, legacy refactoring, or any task that benefits from senior-level push-back on anti-patterns | Simple single-line edits where the pattern is already clear |
| **Deep Research** | `deep-research/SKILL.md` | Complex bugs, external-repo intake, architecture comparisons, or any investigation that benefits from parallel fact-gathering branches and synthesis | Quick grep-and-fix tasks; straightforward explain questions |
| **External Adaptation** | `external-adaptation/SKILL.md` | Evaluating a GitHub repo, trending library, or external pattern for adoption; porting an upstream example into this codebase | The task does not involve any external code or dependency |

## Companion Files

The `senior-laravel-developer/` skill includes supplementary checklists:

| File | Purpose |
|------|---------|
| `security-checklist.md` | Tenant isolation, auth, CSRF, XSS, file upload checks |
| `performance-playbook.md` | N+1, pagination, caching, query optimization patterns |
| `architecture-patterns.md` | Util layer, controller shape, module boundaries, hook patterns |

## Adding a New Skill

1. Create `<skill-name>/SKILL.md` under `.cursor/skills/`.
2. Include YAML front-matter with `name` and `description` (the description doubles as the agent's trigger hint).
3. Add an entry to this index table with clear trigger phrases and exclusions.
4. If the skill has companion files, list them in the Companion Files section.
