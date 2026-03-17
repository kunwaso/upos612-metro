# UI Components Reference — Metronic 8.3.3 (Project-Wide)

> **RULE FOR ALL CODING AGENTS:**
> Before writing any Blade view or UI markup, read this file first.
> The **entire project** uses **Metronic 8.3.3** (Bootstrap 5). No other theme (e.g. Trezo) is used.
> **Never invent new CSS classes or layout patterns.** Use only Metronic/Bootstrap 5 patterns from the HTML reference.

> **Scope:** All Blade views — core (`resources/views/`) and modules (e.g. `Modules/ProjectX/Resources/views/`). **Core** uses `public/html/` (HTML reference) and `public/assets/` (CSS/JS/media). **ProjectX** uses `asset('modules/projectx/...')` and can reference `Modules/ProjectX/Resources/html/` or `public/html/`.

---

## Theme Identity

| Property | Value |
|----------|--------|
| Theme name | **Metronic 8.3.3** |
| Framework | Bootstrap 5 |
| Icons | Keenicons (`ki-duotone ki-{name}` with `<span class="pathN"></span>` children) |
| HTML reference (core) | `public/html/` |
| HTML reference (ProjectX) | `Modules/ProjectX/Resources/html/` or `public/html/` |
| Assets (core) | `public/assets/` → use `asset('assets/...')` in Blade |
| Assets (ProjectX) | `public/modules/projectx/` → use `asset('modules/projectx/...')` |

---

## 1. HTML Component Reference (Project-Wide)

Before writing any Blade markup, find the matching component:

- **Core views:** `public/html/`
- **ProjectX:** `Modules/ProjectX/Resources/html/` or `public/html/`

Same structure under both (e.g. subfolders: layouts, dashboards, forms, widgets, apps, utilities).

| Need | Look in (`public/html/` or `Modules/ProjectX/Resources/html/`) |
|------|------------------------------------------------------------------|
| Page layouts, sidebars, headers | `html/layouts.html`, `html/asides.html`, `html/toolbars.html` |
| Dashboard cards & charts | `html/dashboards/*.html` |
| Data tables & listings | `html/apps/ecommerce/sales/listing.html`, `html/apps/customers/list.html`, `html/apps/user-management/users/list.html` |
| Forms, inputs, selects, editors | `html/forms/*.html`, `html/forms/editors/*.html` |
| Modals & popups | `html/utilities/modals/**/*.html` |
| Wizards & steppers | `html/utilities/wizards/*.html` |
| Cards, statistics, KPI widgets | `html/widgets/statistics.html`, `html/widgets/mixed.html`, `html/widgets/feeds.html` |
| User profiles & account pages | `html/account/*.html`, `html/pages/user-profile/*.html` |
| Authentication pages | `html/authentication/**/*.html` |
| Invoices | `html/apps/invoices/**/*.html` |
| Chat | `html/apps/chat/*.html` |
| Search overlays | `html/utilities/search/*.html` |

---

## 2. UI Workflow

1. **IDENTIFY** — What UI element do you need? (card, table, form, modal, widget)
2. **FIND** — Open the matching HTML reference in `public/html/` (core) or `Modules/ProjectX/Resources/html/`
3. **COPY** — Use the exact HTML structure, classes, and data attributes
4. **ADAPT** — Replace static text with `{{ }}`, add `@csrf`, wire routes with `route()`
5. **VERIFY** — Core: `asset('assets/...')`; ProjectX: `asset('modules/projectx/...')`; no invented classes

---

## 3. Allowed Class Patterns (Metronic / Bootstrap 5 only)

| Element | Classes |
|--------|---------|
| Cards | `card`, `card-flush`, `card-body`, `card-header`, `card-title`, `card-toolbar` |
| Tables | `table`, `table-row-dashed`, `table-row-gray-300`, `align-middle`, `gs-0`, `gy-4` |
| Buttons | `btn`, `btn-primary`, `btn-light-primary`, `btn-sm`, `btn-icon`, `btn-hover-rise` |
| Badges | `badge`, `badge-light-success`, `badge-light-danger`, `badge-light-warning` |
| Forms | `form-control`, `form-select`, `form-check`, `form-label`, `form-control-solid` |
| Spacing | `mb-5`, `mb-lg-10`, `pt-7`, `px-9`, `me-2`, `ms-3` (Bootstrap 5 utilities) |
| Text | `text-gray-900`, `text-gray-700`, `text-muted`, `fw-bold`, `fw-semibold`, `fs-6`, `fs-7` |
| Grid | `row`, `col-md-6`, `col-lg-4`, `col-xl-3`, `g-5`, `g-xl-10` |
| Icons | `ki-duotone ki-{name}` with `<span class="path1"></span>` … children |
| Modals | `modal`, `modal-dialog`, `modal-content`, `modal-header`, `modal-body`, `modal-footer` |
| Tabs | `nav nav-tabs`, `nav-link`, `tab-content`, `tab-pane` |
| Alerts | `alert`, `alert-dismissible`, `alert-primary` |

---

## 4. Asset Paths

**Core (root):**

- **HTML reference:** `public/html/` (Metronic 8.3.3 UI HTML)
- **Assets:** `public/assets/` (CSS, JS, plugins, media)
- In Blade: `asset('assets/css/style.bundle.css')`, `asset('assets/plugins/global/plugins.bundle.js')`, `asset('assets/media/...')`

**ProjectX:**

- Source: `Modules/ProjectX/Resources/assets/`
- Published: `public/modules/projectx/`
- In Blade: `asset('modules/projectx/css/style.bundle.css')`, `asset('modules/projectx/plugins/global/plugins.bundle.js')`, `asset('modules/projectx/media/...')`

**Never:**

- Use `asset('modules/projectx/assets/...')` — no nested `assets/` segment
- Invent new CSS classes — only use what exists in Metronic (`public/assets/` or ProjectX bundle)

---

## 5. Date and Time Inputs

- **Do not use native HTML5 date/time widgets:** no `type="date"`, `type="time"`, or `type="datetime-local"`.
- **Single date:** Use a text input with Metronic form classes and initialize Flatpickr in page JS. Submit values in `Y-m-d` format.
- **Datetime:** Use Flatpickr with time enabled; submit e.g. `Y-m-d\TH:i` when backend expects it.
- **Date ranges:** Use Daterangepicker per Metronic `public/html/` or `Resources/html` examples.

### 5.1 Root Form Controls (Core Standard)

Use this as the default pattern in `resources/views/` for all root form updates.

- **Text and number**
  - Input class: `form-control form-control-solid`
  - Keep existing JS hooks such as `input_number` when behavior depends on them.
  - Label class: `form-label`; add `required` when the field is mandatory.
- **Select (Select2)**
  - Select class: `form-select form-select-solid`
  - Add `data-control="select2"` (or `data-kt-select2="true"` when required by existing markup).
  - Include an empty first option for placeholder usage: `<option value=""></option>`.
  - Use `data-placeholder="..."`.
  - Use `data-allow-clear="true"` when clear is allowed.
  - Use `data-hide-search="true"` for short option lists.
  - In modals, set `data-dropdown-parent="#modal_id"` on the select.
- **Date**
  - Use `<input type="text" class="form-control form-control-solid" ...>`.
  - Initialize Flatpickr in page JS; no native `type="date"`.
- **Date range**
  - Use the same solid text input class.
  - Initialize Daterangepicker (or Flatpickr range mode) in page JS with business date format.
- **Checkbox**
  - Wrapper: `form-check form-check-custom form-check-solid`
  - Input: `form-check-input`
  - Label: `form-check-label`
  - Switches: add `form-switch` on wrapper.
- **Radio**
  - Wrapper: `form-check form-check-custom form-check-solid`
  - Input: `form-check-input` with `type="radio"`
  - Label: `form-check-label`

---

## 6. Design Principles (Within Metronic)

Apply these when building or reviewing UI. All choices stay within the Metronic 8.3.3 system; do not introduce new type scales, palettes, or motion libraries.

| Principle | Guidance |
|-----------|----------|
| **Typography** | Use Metronic’s type scale only (`fs-6`, `fs-7`, `fw-bold`, `fw-semibold`, `text-gray-*`, `text-muted`). Do not introduce new font families or custom font stacks. |
| **Color & contrast** | Use the Metronic palette and utility classes. Ensure text has sufficient contrast (WCAG). Avoid gray or muted text on colored backgrounds; reserve `text-muted` / `text-gray-*` for use on light, neutral backgrounds. |
| **Motion** | If adding custom JS/CSS animation, keep it subtle. Prefer short, purposeful transitions. Respect `prefers-reduced-motion` when implementing custom motion (e.g. hide or shorten animations when the user prefers reduced motion). |
| **UX writing** | Use existing translation keys (`__('lang_v.key')`, `__('messages.key')`). Button labels and CTAs should be clear and action-oriented. Error and validation messages should be specific and helpful; empty states should explain what the user can do next. |

---

## 7. Design Anti-Patterns (Avoid)

| Anti-pattern | Why to avoid | Do instead |
|--------------|--------------|------------|
| Gray text on colored backgrounds | Poor contrast and readability. | Use muted/gray text only on light neutrals; use white or high-contrast text on colored areas. |
| Cards nested inside cards | Visual clutter and unclear hierarchy. | Use a single card with sections, or a list/table; add a card only when the reference shows a distinct nested block. |
| Bounce or elastic easing | Feels dated and can trigger motion sensitivity. | Use subtle ease or linear for any custom animation; match Metronic’s transition style when present. |
| Pure black or flat gray | Harsh and less accessible. | Use Metronic’s grays (`text-gray-900`, `text-gray-700`, etc.); the theme already uses tinted neutrals. |
| Invented or ad-hoc classes | Breaks theme consistency and increases maintenance. | Use only classes from the HTML reference and §3 Allowed Class Patterns. |
| Long or vague button/error copy | Confuses users and hurts accessibility. | Use translation keys with short, clear labels and specific error messages. |

---

## 8. Validation Before Finishing

- [ ] Markup structure matches a reference in `public/html/` or `Modules/ProjectX/Resources/html/`
- [ ] Only Metronic/Bootstrap 5 classes used; no invented classes
- [ ] Asset paths: core `asset('assets/...')`, ProjectX `asset('modules/projectx/...')` (no extra `assets/` segment in ProjectX)
- [ ] Icons use `ki-duotone` with proper `<span class="pathN">` children where required
- [ ] No native `type="date"`, `type="time"`, or `type="datetime-local"`; use Flatpickr or Daterangepicker
- [ ] Design principles (§6) and anti-patterns (§7) considered for new or touched UI

For full policy and core vs module asset details, see **AGENTS.md** Section 10.
