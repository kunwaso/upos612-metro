# Cursor Rules Index

This folder contains `.mdc` rule files that Cursor loads automatically based on their `alwaysApply` and `globs` settings.

## Rules

| Rule file | Scope | Always apply? | Purpose |
|-----------|-------|---------------|---------|
| `laravel-coding-constitution.mdc` | All files | **Yes** | Core Laravel architecture rules: Blade is presentation-only, controllers are thin, validation in FormRequest, business logic in Utils, view data prepared before render. |
| `clarification-gate.mdc` | All files | **Yes** | Requires agents to restate goal, list assumptions, and ask up to 3 blocking questions before editing on non-trivial implement tasks. |
| `blade-refactor-clean-architecture.mdc` | `**/*.blade.php` | No (glob-triggered) | When touching Blade files with heavy `@php` logic, refactor to clean architecture (Controller/Util/composer). |
| `blade-ui-style-enforcement.mdc` | `resources/views/**/*.blade.php` | No (glob-triggered) | Enforce Metronic 8.3.3 UI patterns in core Blade views. |
| `projectx-module-ui.mdc` | `Modules/ProjectX/**/*.blade.php`, `*.php`, `*.js` | No (glob-triggered) | Metronic 8.3.3 UI rules specific to the ProjectX module (assets, components, markup). |
| `ui-layout-first-then-data.mdc` | `resources/views/**/*.blade.php`, `**/Resources/views/**/*.blade.php`, `app/Http/Controllers/**/*.php` | No (glob-triggered) | When building UI from a reference template, match layout structure first, then wire controller data. |
| `migration-before-ui.mdc` | `**/*settings*.blade.php`, `**/*SettingsRequest.php`, `**/fabric_manager/*.blade.php` | No (glob-triggered) | Ensure a migration exists for every persisted form/settings field before building UI. |
| `external-adaptation-safety.mdc` | `**/*.php`, `**/*.blade.php`, `**/*.js`, `**/*.ts` | No (glob-triggered) | Safety guardrails when adapting external repos, packages, or upstream examples into this codebase. |
| `projectauto-predefined-only.mdc` | `Modules/Projectauto/**/*.{php,blade.php,js}` | No (glob-triggered) | Projectauto workflows must use the predefined-only trigger/condition/action contract. |

## How rules work

- **`alwaysApply: true`** rules are loaded on every agent turn regardless of which files are open.
- **`alwaysApply: false`** rules are loaded only when the agent is working with files matching the `globs` pattern.
- Rules are applied in addition to `AGENTS.md` and `ai/*.md` domain docs.
- If a rule conflicts with `AGENTS.md`, `AGENTS.md` takes priority (see `AGENTS.md` section 0.1).
