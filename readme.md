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

## Installation & Documentation

You will find installation guide and documentation in the downloaded zip file.
Also, For complete updated documentation of the ultimate pos please visit online [documentation guide](http://ultimatefosters.com/ultimate-pos/).

## Security Vulnerabilities

If you discover a security vulnerability within ultimate POS, please send an e-mail to support at [thewebfosters@gmail.com](mailto:thewebfosters@gmail.com). All security vulnerabilities will be promptly addressed.

##build me a detail plan  implement phase to phase , task by task with todolist make sure agents coding correctly 

The Ultimate POS software is licensed under the [Codecanyon license](https://codecanyon.net/licenses/standard).

## Run the log-scan: look at the newest Laravel log and fix any issues.  run all autofixes

##“@.cursor/rules/ui-layout-first-then-data.mdc — rebuild the business settings page: layout match first, then controller data.”

##“Copy public/html/[FOLDER] into resources/views/[FOLDER]. Each HTML → Blade: extend layouts.app, put only the content inside #kt_content into @section('content'), use asset('assets/...'). No full page shell—layout already has it.”

## after created a plan alway ask. what this plan actually do?

## build me a detail plan  implement phase to phase , task by task with todolist make sure agents coding correctly  refference ui style @public/html  [https://preview.keenthemes.com/html/metronic/docs/base/utilities](https://preview.keenthemes.com/html/metronic/docs/base/utilities) ( ask me question to make sure plan correctly )


git init
git add README.md
git commit -m "first commit"
git branch -M main
git remote add origin https://github.com/kunwaso/upos612-metro.git
git push -u origin main


php artisan vendor:publish --tag=cms-assets --force

write me a prompt to rebuild the view as above dicussion

write me a prompt to implement detail phase by phase task by task with detail todo list make sure when user search it run and show correct data