---
name: DataTables buttons root defaults
overview: Enable the DataTables Buttons extension (export CSV, Excel, PDF, print, column visibility) for all DataTables app-wide by setting root-level defaults after the Metronic datatables bundle loads, so every table that does not override these options gets the button group—only for org admin users.
todos: []
isProject: false
---

# DataTables export/buttons at root base (org admin only)

## Current state

- **Bundle:** [public/assets/plugins/custom/datatables/datatables.bundle.js](public/assets/plugins/custom/datatables/datatables.bundle.js) includes DataTables 2.3.7 and the Buttons extension (copy, excel, csv, pdf, print, colvis) with Bootstrap 5 styling (`dt-buttons btn-group`).
- **Layout:** [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php) loads the bundle (line 4070), then `@include('layouts.partials.javascripts')` (line 4084).
- **Why buttons don’t show:** The bundle’s Bootstrap 4 integration block (inside the same file, ~line 14352) sets `DataTable.defaults.dom` to a custom string that **does not include `B`** (the buttons placeholder). So the Buttons extension is present but the default layout never reserves a place for it. No page sets `dom` or `buttons` except two that use a minimal `dom` (contact ledger: `dom: 't'`, restaurant booking: `dom: 'frtip'`).
- **Existing central tweak:** [resources/views/layouts/partials/javascripts.blade.php](resources/views/layouts/partials/javascripts.blade.php) already adjusts DataTables (e.g. `$.fn.dataTable.ext.errMode`) and styles `.dt-buttons.btn-group` (lines 211–212).

## Approach

Set **DataTable.defaults** in one central place so that:

1. The default **dom** includes the buttons container (prepend `B` to the current default `dom`).
2. The default **buttons** list is `['copy','excel','csv','pdf','print','colvis']`.
3. **Only when the user is org admin:** use the same logic as elsewhere in the app (`App\Utils\Util::is_admin($user, $business_id)` i.e. user has role `Admin#<business_id>`). For non–org-admin users, do **not** set buttons or alter dom, so they never see the export/column-visibility button group.

Any table that does **not** pass its own `dom` or `buttons` will get the button bar **only for org admin**. Tables that explicitly set `dom` (e.g. ledger `dom: 't'`) or `buttons: false` / `buttons: []` keep their current behavior.

## Implementation steps

### 1. Provide `is_org_admin` to layout views

**File:** [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php)

- In the existing `View::composer(['layouts.*'], ...)` (around line 94), add a variable so that layout/partials have `$is_org_admin` when the user is the business/org admin.
- Logic: when `Auth::check()` and `session()->has('business.id')`, set `$is_org_admin = (new \App\Utils\Util)->is_admin(Auth::user(), session('business.id'));` otherwise `$is_org_admin = false`. Pass it with `$view->with('is_org_admin', $is_org_admin)` (inside the same `if (isAppInstalled())` block so uninstalled app doesn’t rely on session).
- This makes `$is_org_admin` available in [resources/views/layouts/partials/javascripts.blade.php](resources/views/layouts/partials/javascripts.blade.php) when it is included from the main layout.

### 2. Set DataTables default `dom` and `buttons` only for org admin

**File:** [resources/views/layouts/partials/javascripts.blade.php](resources/views/layouts/partials/javascripts.blade.php)

- Add a small script block that runs **after** the datatables bundle and **before** any page-specific `.DataTable()` inits.
- Output a JS flag from Blade: e.g. `window.__datatable_show_export_buttons = @json($is_org_admin ?? false);` so that when the user is not org admin (or not logged in) the value is `false`.
- In the same script block, only when `window.__datatable_show_export_buttons === true` and `$.fn.dataTable` and `$.fn.dataTable.defaults` exist:
  - Set `defaults.buttons = ['copy','excel','csv','pdf','print','colvis']`.
  - If `defaults.dom` is set, set `defaults.dom = 'B' + defaults.dom`.
- Run this at load time (not inside `document.ready`) so defaults are in place before any inline page script that creates a DataTable.

**Exact logic:**

- Blade: `window.__datatable_show_export_buttons = @json($is_org_admin ?? false);`
- Guard: `if (window.__datatable_show_export_buttons && typeof $ !== 'undefined' && $.fn.dataTable && $.fn.dataTable.defaults) { ... }`
- `$.fn.dataTable.defaults.buttons = ['copy','excel','csv','pdf','print','colvis'];`
- `if ($.fn.dataTable.defaults.dom) { $.fn.dataTable.defaults.dom = 'B' + $.fn.dataTable.defaults.dom; }`

### 3. No changes to individual views

- Do **not** add `dom` or `buttons` to each of the 30+ DataTable inits across the app. Rely on the new defaults so that:
  - Home dashboard tables (sales order, shipments, purchase order, purchase requisition, cash flow, etc.), sell index, sales order index, product index, and all other tables that currently omit `dom`/`buttons` will get the button group automatically.
- The two views that set a custom `dom` ([resources/views/contact/show.blade.php](resources/views/contact/show.blade.php) `dom: 't'`, [resources/views/restaurant/booking/index.blade.php](resources/views/restaurant/booking/index.blade.php) `dom:'frtip'`) override the default and will continue to have no buttons unless they are later changed to include `B` and optionally `buttons`.

### 4. Optional: allow a view to opt out

- If a specific table must have no buttons, that view can pass `buttons: false` or `buttons: []` in its DataTable options. No change required for the two tables that already use a minimal `dom`; they already opt out by not including `B`.

### 5. Server-side tables and export scope

- Many tables use `serverSide: true`. With the default Buttons extension, export (copy/csv/excel/pdf) will use **only the current page** of data. Full-data export would require a server-side export endpoint and/or custom buttons; that is out of scope for this plan. The plan only enables the standard client-side button set app-wide.

### 6. Verification

- As **org admin:** open a page with a DataTable that does not set `dom`/`buttons` (e.g. dashboard Sales Order table, or sell index). Confirm the dt-buttons btn-group appears above the table with copy, Excel, CSV, PDF, print, and column visibility.
- As **non–org-admin** (e.g. Cashier or other role): same pages should show the DataTable **without** the export/buttons group.
- Confirm the two views with custom `dom` (contact ledger, restaurant booking) still render as before (no buttons).
- Run existing tests that touch DataTables if any; fix any that assume the previous default layout.

## Summary


| What                                                                            | Where                                                                                                                        |
| ------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| Provide `is_org_admin` to layout views                                          | [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) (existing `View::composer(['layouts.*'], ...)`) |
| Set default `buttons` and prepend `B` to default `dom` only when `is_org_admin` | [resources/views/layouts/partials/javascripts.blade.php](resources/views/layouts/partials/javascripts.blade.php)             |
| No edits to datatables.bundle.js                                                | Leave bundle as-is                                                                                                           |
| No per-view changes                                                             | All tables that don’t override get buttons when user is org admin                                                            |


**Org admin** is determined by `App\Utils\Util::is_admin($user, $business_id)` (user has role `Admin#<business_id>`). Only those users see the DataTables export/column-visibility button group by default.