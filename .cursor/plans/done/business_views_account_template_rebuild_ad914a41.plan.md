---
name: Business Views Account Template Rebuild
overview: Rebuild the business settings page using the account template. Work is split into (A) copy and edit the Blade view from public/html/account first (structure and titles only), then (B) wire controller data into that view. Template-first, data-second so agents code correctly.
todos:
  - id: A1-layout-toolbar
    content: "Template: Add @yield('toolbar') to layouts/app from account HTML"
    status: completed
  - id: A2-copy-toolbar
    content: "Template: Copy account toolbar into business/settings Blade; static titles, placeholder actions"
    status: completed
  - id: A3-copy-card-nav
    content: "Template: Copy account card + nav-line-tabs; 17 tab labels; no @include yet"
    status: completed
  - id: A4-pane-js
    content: "Template: Add pane wrapper + 17 pos-tab-content divs + tab-switch JS"
    status: completed
  - id: A5-verify
    content: "Verify Part A: page renders, tabs switch, no controller data required"
    status: in_progress
  - id: B1-controller-data
    content: "Data: Document all getBusinessSettings() view variables"
    status: completed
  - id: B2-wire-form
    content: "Data: Wire form action/id, submit, search_settings"
    status: completed
  - id: B3-wire-partials
    content: "Data: Replace placeholder panes with @include 17 partials"
    status: completed
  - id: B4-verify
    content: "Verify Part B: form submits, test email/SMS, TinyMCE, fileinput"
    status: in_progress
  - id: C1-partials-copy
    content: "Partials: Copy account form layout into each partial (placeholders)"
    status: completed
  - id: C2-partials-wire
    content: "Partials: Wire controller vars and Form::*; keep all name/id"
    status: completed
  - id: C3-verify
    content: "Verify Part C: all tabs save; linter clean"
    status: in_progress
  - id: D1-register
    content: "Optional: align register.blade.php with account/auth style"
    status: pending
  - id: D2-docs
    content: Update ai/known-issues.md or docs for account-template structure
    status: pending
isProject: false
---

# Business Views Rebuild — Copy Template First, Then Controller Data

## Principle: Template first, then data

1. **Part A — Copy and edit from [public/html/account](public/html/account):** Build the Blade view by copying the account HTML (toolbar, card, nav-line-tabs, pane wrapper). Use **static titles and placeholder content** so the page renders with the correct look. No dependency on controller variables at this stage.
2. **Part B — Wire controller data:** Update the view to use data from [BusinessController::getBusinessSettings()](app/Http/Controllers/BusinessController.php) (form action, `$business`, `$currencies`, etc.), add the real form and `@include` partials, and ensure POST and JS work.
3. **Part C — Partials:** For each partial, (C1) copy form layout from account HTML into Blade (Metronic markup, placeholders), then (C2) wire controller variables and `Form::`* while keeping every `name`/`id`.

This order keeps agents from mixing template edits with data wiring.

## Scope and references

- **In scope:** [resources/views/business/settings.blade.php](resources/views/business/settings.blade.php) and 17 partials in [resources/views/business/partials/](resources/views/business/partials/). Optionally [resources/views/business/register.blade.php](resources/views/business/register.blade.php).
- **Template source:** [public/html/account/settings.html](public/html/account/settings.html) — toolbar (approx. 4390–4512), card with nav-line-tabs (approx. 4518–4797), form cards (approx. 4800+). Use only Metronic 8.3.3 per [ai/ui-components.md](ai/ui-components.md).
- **Controller:** [app/Http/Controllers/BusinessController.php](app/Http/Controllers/BusinessController.php) — `getBusinessSettings()` returns `view('business.settings', compact(...))` with `business`, `currencies`, `tax_rates`, and 15+ other variables (see L359).

## Current vs target (high level)


| Current                                                                                                                                                         | Target                                                                                                       |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| `content-header` + `section.content`                                                                                                                            | Account-style toolbar + card with horizontal tabs                                                            |
| Sidebar: `pos-tab-menu` + `list-group` (17 items)                                                                                                               | Horizontal: `nav nav-stretch nav-line-tabs nav-line-tabs-2x` (17 items)                                      |
| Tab panes: `pos-tab-content` inside `@component('components.widget')`                                                                                           | Same 17 `pos-tab-content` panes inside account-style card body                                               |
| Tab switch: [public/assets/app/js/common.js](public/assets/app/js/common.js) L341–348 (`div.pos-tab-menu>div.list-group>a` → `div.pos-tab>div.pos-tab-content`) | Page-specific JS: bind to new nav, show/hide panes by index (keep same pane structure so form/IDs unchanged) |
| Submit button in a row below widget                                                                                                                             | In toolbar "Actions" area (account pattern)                                                                  |


---

## Part A: Copy and edit Blade view from account HTML (template only)

**Goal:** New business settings page that looks like account/settings (toolbar + card + horizontal tabs + panes). Copy from account HTML; use static titles and placeholder content. No real form data yet.

### A.1 Layout — toolbar yield

- **File:** [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php)
- **Task:** Copy account structure: add `@hasSection('toolbar')` and `@yield('toolbar')` between header and `#kt_content_container` (account HTML lines 4390–4512).

### A.2 Settings Blade — toolbar (copy from account, static titles)

- **File:** [resources/views/business/settings.blade.php](resources/views/business/settings.blade.php)
- **Task:** Add `@section('toolbar')` with account-style markup: `toolbar d-flex flex-stack py-3 py-lg-5`, `container-xxl`, `page-title` (title `@lang('business.business_settings')`), breadcrumb (e.g. Home > Business > Settings), and in the Actions area: (1) include search_settings (e.g. in a dropdown or compact block so behavior is preserved), (2) submit button “Update settings” with Metronic classes (`btn btn-sm btn-primary`). Do not remove the form; keep the form wrapping the entire content and move the submit button into the toolbar (use `form="bussiness_edit_form"` on the button if the form is below).
- **Todo:** Toolbar renders with correct Metronic classes and titles; no dependency on controller data.

### A.3 Settings Blade — card and nav-line-tabs (copy from account)

- **File:** [resources/views/business/settings.blade.php](resources/views/business/settings.blade.php)
- **Task:** Replace the current content structure (widget + `pos-tab-menu` + `pos-tab`) with: (1) One card: `card mb-5 mb-xl-10`, `card-body pt-9 pb-0`. (2) Inside the card body: only the horizontal tab list (no profile/avatar block needed). Use `ul.nav.nav-stretch.nav-line-tabs.nav-line-tabs-2x.border-transparent.fs-5.fw-bold` and 17 `li.nav-item` with `a.nav-link` for Business, Tax, Product, Contact, Sale, POS, Display screen, Purchase, Payment, Dashboard, System, Prefixes, Email, SMS, Reward point, Modules, Custom labels (same order as current list-group). (3) Below the card, a single wrapper `div` that contains the 17 existing `@include` partials in the same order. Keep each partial’s root as `pos-tab-content` for now so pane structure is unchanged.
- **Todo:** Account card and 17 nav tabs visible; no partials included yet.

### A.4 Settings Blade — pane wrapper and tab JS

- **File:** [resources/views/business/settings.blade.php](resources/views/business/settings.blade.php) (in `@section('javascript')`)
- **Task:** Add a script that runs on DOM ready and binds click on the new horizontal `.nav-link` (within the business settings card). On click: prevent default, remove `active` from all these nav-links and add to current; get index of clicked item; remove `active` from all `.pos-tab-content` inside the business settings pane wrapper, add `active` to the pane at the same index. Use a scoped container (e.g. `#business-settings-tabs`) so this does not affect other pages. Do not rely on `common.js` for this page’s tab switch (the sidebar no longer exists here).
- **Todo:** Tab switching works for 17 panes; placeholders only; no form or JS features yet.

### A.5 Verification (Part A)

- **Todo:** Open business/settings; confirm toolbar, card, 17 tabs, 17 panes with placeholder text; tab switching works. No errors when controller still passes existing compact().

---

## Part B: Wire controller data into the Blade view

**Goal:** Connect [BusinessController::getBusinessSettings()](app/Http/Controllers/BusinessController.php) data to the view: form action, form id, submit button, search_settings, and include the 17 partials so the form posts and data loads.

### B.1 Document controller data

- **Task:** List all variables passed to `view('business.settings', compact(...))` (business, currencies, tax_rates, timezone_list, months, accounting_methods, commission_agent_dropdown, units_dropdown, date_formats, shortcuts, pos_settings, modules, theme_colors, email_settings, sms_settings, mail_drivers, allow_superadmin_email_settings, custom_labels, common_settings, weighing_scale_setting, payment_types). Ensure the plan and partials use only these.
- **Todo:** Checklist of variable names for agents.

### B.2 Wire form and toolbar actions

- **File:** [resources/views/business/settings.blade.php](resources/views/business/settings.blade.php)
- **Task:** Wrap the card and panes in `Form::open` (action = postBusinessSettings, id = bussiness_edit_form, files = true). In toolbar Actions: add `@include('layouts.partials.search_settings')` and submit button with `form="bussiness_edit_form"`. Use controller routes and translations.
- **Todo:** Form submits; search_settings and submit in toolbar.

### B.3 Wire partials into panes

- **File:** [resources/views/business/settings.blade.php](resources/views/business/settings.blade.php)
- **Task:** Replace 17 placeholder pane contents with `@include('business.partials.settings_*')` in the same order as tabs. First partial root has `pos-tab-content active`. Keep tab-switching JS.
- **Todo:** All 17 partials render; form includes all fields; tab switch still works.

### B.4 Verification (Part B)

- **Todo:** Submit form; POST succeeds; test email, test SMS, TinyMCE, fileinput, search_settings work. No 422/500.

---

## Part C: Partials — copy account form layout, then wire data

**Goal:** Each partial uses Metronic form markup from account HTML; then wire controller variables and Form::* without changing any input `name` or `id`.

### C.1 Partials — copy form layout from account HTML (template)

- **Files:** All [resources/views/business/partials/settings_*.blade.php](resources/views/business/partials/) (settings_business, settings_tax, settings_product, settings_contact, settings_sales, settings_pos, settings_display_pos, settings_purchase, settings_payment, settings_dashboard, settings_system, settings_prefixes, settings_email, settings_sms, settings_reward_point, settings_modules, settings_custom_labels; and settings_weighing_scale if used).
- **Task:** Ensure each partial’s root element is a single wrapper with class `pos-tab-content` (first one only also has `active` in settings.blade.php). Add a second class for styling if needed (e.g. `tab-pane`-like). Do not remove or rename any form field `name` or `id` used by JS (e.g. `#test_email_btn`, `#display_screen_heading`, `#bussiness_edit_form`).
- **Todo:** Audit all 17 (or 18) partials; ensure consistent root wrapper; no change to field names/IDs.

### C.2 Partials — wire controller data and Form::*

- **Files:** Same partials.
- **Task:** Replace placeholders with real controller variables and Form::* calls. Keep every `name`, `id`, and structure for postBusinessSettings and JS. Add tooltips, conditional visibility. No business logic or @php in Blade.
- **Todo:** All fields bound; form submits; test email/SMS, TinyMCE, fileinput work; linter clean.

### C.3 Verification (Part C)

- **Todo:** Full regression: every tab saves; no 422/500; no Blade or JS errors; linter clean on changed files.

---

## Part D: Optional and docs

### D.1 Register page (optional)

- **File:** [resources/views/business/register.blade.php](resources/views/business/register.blade.php)
- **Task:** Only if desired: adjust CSS/classes to match account or auth pages (e.g. from [public/html/account/](public/html/account/) or auth HTML) without changing layout or stepper logic. No change to form action, fields, or validation.
- **Todo:** If scope includes register, apply minimal visual alignment; otherwise skip.

### D.2 Documentation

- **Task:** If [ai/known-issues.md](ai/known-issues.md) or project docs mention business settings UI or “pos-tab”, state that business settings use the account template (public/html/account) so future agents don’t reintroduce the old pattern.

---

## Agent implementation rules

- **Template first:** In Part A and C.1, do not add controller-specific variables or form actions until Part B and C.2.
- **Data second:** When wiring data (Part B, C.2), do not change the template structure (classes, layout) copied from account HTML.
- **Blade:** No business logic in Blade; no @php for defaulting; all view data from controller (per [.cursor/rules/laravel-coding-constitution.mdc](.cursor/rules/laravel-coding-constitution.mdc)).
- **Forms:** Keep `action`, `method`, `id="bussiness_edit_form"`, `files => true`, and every input `name`/`id` unchanged.
- **UI:** Only Metronic 8.3.3 / Bootstrap 5 classes from [ai/ui-components.md](ai/ui-components.md) and [public/html/account/settings.html](public/html/account/settings.html).
- **Verification:** After each part (and after each partial in C.2), run Read lints and quick manual test.

---

## Todo list (summary for tracking)


| ID  | Task                                                   | Part |
| --- | ------------------------------------------------------ | ---- |
| A.1 | Layout: Add @yield toolbar from account HTML           | A    |
| A.2 | Copy account toolbar into settings Blade; placeholders | A    |
| A.3 | Copy account card + nav-line-tabs; 17 tab labels       | A    |
| A.4 | Pane wrapper + 17 pos-tab-content divs + tab JS        | A    |
| A.5 | Verify Part A: page renders, tabs switch               | A    |
| B.1 | Document getBusinessSettings() view variables          | B    |
| B.2 | Wire form action/id, submit, search_settings           | B    |
| B.3 | Replace placeholder panes with @include partials       | B    |
| B.4 | Verify Part B: form submits, test email/SMS, etc.      | B    |
| C.1 | Partials: Copy account form layout (placeholders)      | C    |
| C.2 | Partials: Wire controller vars and Form::*             | C    |
| C.3 | Verify Part C: all tabs save; linter clean             | C    |
| D.1 | Optional: register.blade.php account/auth style        | D    |
| D.2 | Update ai/known-issues.md or docs                      | D    |

