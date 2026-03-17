# Known Issues, Anti-Patterns & Technical Debt

This document records real findings from codebase analysis.
Review this before starting any feature work to avoid known traps.

**When to update this document:**
- **Add:** When you diagnose a non-obvious bug or trap (e.g. after fixing "button stops working after tab switch"), add a short entry or "Reasoning" for that area so future work avoids the same trap.
- **Update/remove:** When you fix something that is already listed here, update the entry (e.g. mark as resolved or remove it) so the doc stays accurate.

---

## 1. Critical: Security / Data Leaks

### 1.1 `$guarded = ['id']` on Models

**Location:** All models (Transaction.php, Contact.php, etc.)

All models use `protected $guarded = ['id']` instead of explicit `$fillable`.
This means **all columns are mass-assignable except `id`**.

**Risk:** If new database columns are added, they may become mass-assignable by accident.

**Recommendation:** When creating new models, use explicit `$fillable` arrays.
When touching existing models that need new fields, do NOT change the `$guarded` pattern — maintain consistency.

---

### 1.2 No Global business_id Scope

**Risk:** There is no Eloquent global scope enforcing `business_id` filtering.
Every query must manually include `->where('business_id', $business_id)`.

If a developer forgets, the query returns data from ALL businesses.

**Mitigation strategy:** Always query pattern:
```php
$business_id = request()->session()->get('user.business_id');
Model::where('business_id', $business_id)->...
```

---

## 2. Architecture Issues

### 2.1 Fat Controllers

**Affected files:** Most controllers, especially:
- `HomeController.php` (very large; contains dashboard aggregation logic)
- `SellController.php`
- `ReportController.php`

**Problem:** Heavy business logic inside controller methods that should be in Util classes.

**Guideline:** Do NOT make controllers fatter. When touching these files:
- Extract repeated query logic into the appropriate Util
- Do not add more code to already-large methods
- New functionality should go into Util classes

---

### 2.2 HomeController Dashboard Overload

**File:** `app/Http/Controllers/HomeController.php` (very large)

The `index()` method aggregates many dashboard widgets and metrics in one place.
This method is a prime N+1 risk zone.

**When adding new dashboard widgets:**
1. Add a separate controller method or dedicated Util method
2. Use AJAX-loaded widget endpoints instead of loading everything in `index()`
3. Cache expensive aggregations: `Cache::remember("business_{$id}_metric", 3600, fn() => ...)`

---

### 2.3 AdminSidebarMenu Middleware Size

**File:** `app/Http/Middleware/AdminSidebarMenu.php` (very large)

The sidebar menu is built as a middleware. This is an anti-pattern — middleware should not contain UI logic.

**Impact on you:** When adding new menu items, edit this file carefully.
Do not refactor it; just add your menu item in the correct section.

---

### 2.4 Blade @php Blocks for View Data (ProjectX and elsewhere)

**Trap:** Some Blade views (e.g. ProjectX `quote-create.blade.php`, `quote-edit.blade.php`, `orders-edit.blade.php`) contain an `@php` block that defaults `projectxNumberFormatConfig` and computes `projectxCurrencyPrecision`, `projectxQuantityStep`, `projectxCurrencyStep`, `projectxQuantityMin`, etc. Copying this pattern into new or touched views **violates** the Laravel constitution and blade-refactor rules.

**Rule:** Do **not** add or duplicate that `@php` block. Prepare these values in the controller (or in `ProjectXNumberFormatUtil` / a view composer) and pass them to the view. See AGENTS.md §2.5a and `ai/laravel-conventions.md` §6.3.

---

### 2.5 Blade directives inside Form helper arguments (parse-error trap)

**Trap:** Do not place Blade directives that emit PHP (for example `@num_format(...)`) inside `Form::text(...)`, `Form::hidden(...)`, `Form::select(...)`, etc. argument lists.

Example problematic pattern:
```blade
{!! Form::text('amount', @num_format($value), [...]) !!}
```

This can compile into nested `<?php ... ?>` in the generated view and trigger parse errors like:
`syntax error, unexpected token "<", expecting ")"`.

**Rule:** For form field values, use plain PHP expressions/functions (e.g. `number_format(...)` or controller-prepared values) instead of Blade directives inside helper arguments.

---

## 3. UI / Theme Issues

### 3.1 UI Theme — Metronic 8.3.3 Project-Wide

**Context:** The entire project uses **Metronic 8.3.3** (Bootstrap 5). Some Blade views may still contain legacy Bootstrap 3 / AdminLTE (or other old) classes.

**Rule:** All NEW Blade views must use Metronic classes exclusively (see `ai/ui-components.md` and `Modules/ProjectX/Resources/html/`). Do not mix old and new theme classes in the same view.

Old class patterns to avoid:
- `box`, `box-header`, `box-body` (AdminLTE)
- `panel`, `panel-body` (Bootstrap 3)
- Any Trezo-specific classes (e.g. `trezo-card`) — no longer used

New class patterns to use:
- Metronic/Bootstrap 5: `card`, `card-header`, `card-body`, `card-title`, `card-toolbar`
- `btn`, `btn-primary`, `btn-light-primary`, `form-control`, `form-select`, etc. (see `ai/ui-components.md`)

---

### 3.2 Metronic Reference and Assets

**Context:** Core uses **`public/html/`** (HTML reference) and **`public/assets/`** (CSS, JS, media) — use `asset('assets/...')` in core Blade. ProjectX uses `asset('modules/projectx/...')` and can reference `Modules/ProjectX/Resources/html/` or `public/html/`.

**Impact:** Use only structures and classes from those references (or documented in `ai/ui-components.md`). See AGENTS.md Section 10.

---

### 3.3 Dark Mode / Layout Behaviour

Metronic layouts may support dark or theme toggles. If the Blade layout does not yet wire theme persistence to user preferences, theme-specific behaviour may depend on layout/JS setup.

---

## 4. Performance Issues

### 4.1 N+1 Risk in Reports

**Affected:** `ReportController.php`, `HomeController.php`

Report queries frequently iterate over transaction collections and lazy-load contacts, locations, or payments.

**Pattern to fix when touching:**
```php
// BEFORE (N+1)
$transactions = Transaction::where('business_id', $bid)->get();
foreach ($transactions as $t) {
    echo $t->contact->name; // lazy load per row
}

// AFTER (eager load)
$transactions = Transaction::where('business_id', $bid)
    ->with('contact:id,name')
    ->get();
```

### 4.2 Missing Indexes on Filter Columns

Some frequently-filtered columns may lack indexes.

**Key columns that should always be indexed:**
- `transactions.type`
- `transactions.status`
- `transactions.transaction_date`
- `transactions.contact_id`
- `transactions.business_id`
- `contacts.business_id`
- `products.business_id`

When adding new filterable columns, always add a migration with `->index()`.

---

## 5. Outdated / Fragile Patterns

### 5.1 Session-Based Business Context

**File:** `app/Http/Middleware/SetSessionData.php`

The multi-tenant context is set once in the session and only refreshed if the `user` session key is missing.

**Trap:** If you update a business setting (name, currency, financial year) in the same request/session, the session data will be stale until the user logs out and back in.

**Workaround:** Force session refresh after critical business settings changes:
```php
$request->session()->forget('user');
$request->session()->forget('business');
$request->session()->forget('currency');
$request->session()->forget('financial_year');
```

### 5.2 helpers.php License Check (pos_boot)

**File:** `app/Http/helpers.php` lines 1–53

The `pos_boot()` function makes an external cURL license verification call.
Do not remove or modify this function.

### 5.3 Module Autoloading

If a module's classes are not found:
1. Check `modules_statuses.json` — ensure module is `true`
2. Run `composer dump-autoload`
3. Run `php artisan module:enable ModuleName`

---

## 6. Missing Patterns (Things Not Yet Implemented)

| Missing Pattern | Impact |
|---|---|
| No Eloquent global scopes for `business_id` | Every query must manually scope |
| No Form Request classes on most existing controllers | Validation mixed into controller methods |
| No dedicated service/repository layer | Business logic spread across Utils and controller bodies |
| No unit tests for Utils | Refactoring Util methods carries hidden risk |
| No API versioning | `routes/api.php` routes have no version prefix |
| No queue-based PDF generation | PDF generation is synchronous — can timeout on large reports |

---

## 7. Duplication / Inconsistencies

### 7.1 Invoice Number Generation Duplicated

Invoice/reference number generation exists in both `TransactionUtil` and directly in some controller methods.
Always use `TransactionUtil` methods for number generation — never generate inline.

### 7.2 Currency Formatting Inconsistency

Some older views format currency manually, while newer views use the `@format_currency` Blade directive.
**Always use `@format_currency($amount)` in new views.**
ProjectX still contains views that use raw `number_format()` and manual symbol concatenation; do not extend that pattern in new or touched code, even if a full cleanup is deferred.

### 7.3 Date Formatting Inconsistency

Some views use PHP `date()` directly, others use Carbon, others use the `@format_date` directive.
**Always use `@format_date($date)` in new views.**

---

## 8. Debugging Traps

| Symptom | Likely Cause |
|---|---|
| "Business not found" error | Session expired or `SetSessionData` didn't run |
| Permission denied on correct role | Spatie permission cache stale — run `permission:cache-reset` |
| Module classes not found | `modules_statuses.json` disabled or autoload not updated |
| Wrong timezone on dates | `Timezone` middleware not in route group or business timezone not set |
| Sidebar menu missing items | `AdminSidebarMenu` middleware not applied to route group |
| CSRF token mismatch | Missing `@csrf` in form or AJAX not sending `X-CSRF-TOKEN` header |
| PDF shows wrong language/direction | `getMpdf()` checks `auth()->user()->language` — ensure user is authenticated |

---

## 9. Do Not Touch

| File/Pattern | Reason |
|---|---|
| `app/Http/helpers.php` — `pos_boot()` function | License validation — modifying breaks the app |
| `modules_statuses.json` — module states | Managed by module:enable/disable commands |
| `config/author.php` | License configuration |
| Existing migration files | Never edit deployed migrations — always create new ones |
| Metronic compiled CSS (e.g. `public/modules/projectx/css/style.bundle.css`) | Built from Metronic source — do not edit directly; re-publish assets if needed |
