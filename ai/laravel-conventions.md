# Laravel Conventions

This document defines every structural and coding convention used in this project.
Every coding agent must follow these conventions without exception.

---

## 1. Project Identity

| Property | Value |
|---|---|
| Framework | Laravel 9 |
| PHP | ^8.0 |
| Architecture | Modular (nwidart/laravel-modules) |
| Multi-tenancy | `business_id` column scoping on every tenant table |
| Business logic layer | `app/Utils/*Util.php` classes extending `App\Utils\Util` |
| Frontend | **Metronic 8.3.3** (Bootstrap 5) project-wide — see `ai/ui-components.md` and AGENTS.md Section 10 |
| API auth | Laravel Passport (guard: `api`) |
| Permissions | spatie/laravel-permission |
| Data tables | Yajra DataTables (AJAX-driven) |
| PDF | mPDF (`$this->getMpdf()` on controllers) |
| Excel | Maatwebsite Excel |

---

## 2. Directory Structure

```
app/
├── Http/
│   ├── Controllers/         ← Core app controllers (verify exact count via `project_map`)
│   ├── Middleware/          ← Middleware files (verify exact count via `project_map`)
│   ├── helpers.php          ← Global helper functions (autoloaded)
│   └── AdminlteCustomPresenter.php
├── Utils/                   ← Business logic layer
│   ├── Util.php             ← Base Util class
│   ├── BusinessUtil.php
│   ├── TransactionUtil.php
│   ├── ProductUtil.php
│   ├── ContactUtil.php
│   ├── TaxUtil.php
│   ├── AccountTransactionUtil.php
│   ├── CashRegisterUtil.php
│   ├── NotificationUtil.php
│   ├── ModuleUtil.php
│   └── RestaurantUtil.php
├── [ModelName].php          ← Models live DIRECTLY in app/ (not app/Models/)
├── Events/
├── Listeners/
├── Exports/
├── Mail/
├── Notifications/
├── Providers/
└── Rules/

Modules/                     ← module folders present in this checkout (verify with filesystem + modules_statuses.json)

resources/views/             ← Core Blade views
database/migrations/         ← Migration files (verify exact count via filesystem/project_map)
database/seeders/
database/factories/
routes/
├── web.php
├── api.php
└── install_r.php
```

---

## 3. Model Conventions

### 3.1 Location
Models live at `app/ModelName.php` — **not** `app/Models/`.

Namespace: `namespace App;`

### 3.2 Standard Model Pattern

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $guarded = ['id'];   // ← Note: uses $guarded, NOT $fillable

    protected $table = 'contacts'; // explicit table name always

    protected $casts = [
        'custom_fields' => 'array',
    ];

    // Relationships always use full namespace
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function transactions()
    {
        return $this->hasMany(\App\Transaction::class, 'contact_id');
    }
}
```

**Important:** All existing models use `protected $guarded = ['id']` — follow this pattern for new models.

### 3.3 Multi-Tenant Scoping
Every query on tenant data **must** include `->where('business_id', $business_id)`.
Get `$business_id` from the session: `request()->session()->get('user.business_id')`.

### 3.4 Transaction Types (reference)
```
purchase | sell | expense | stock_adjustment | sell_transfer
purchase_transfer | opening_stock | sell_return | opening_balance
purchase_return | payroll | expense_refund | sales_order | purchase_order
```

### 3.5 Transaction Status (reference)
```
received | pending | ordered | draft | final | in_transit | completed
```

---

## 4. Controller Conventions

### 4.1 Base Controller

All controllers extend `App\Http\Controllers\Controller` which provides:

```php
$this->respondWithError(string $message)   // returns JSON {success: false, msg: ...}
$this->respondSuccess(string $message)      // returns JSON {success: true, msg: ...}
$this->respondUnauthorized(string $message) // returns JSON 403
$this->respondWentWrong($exception)         // returns JSON with error details
$this->respond(array $data)                 // raw JSON response
$this->getMpdf($orientation = 'P')          // returns mPDF instance
```

### 4.2 Controller Orchestration Pattern

Many existing controllers are historically large, but **new work should keep controllers orchestration-focused** and push reusable business logic into Util classes injected via constructor:

```php
class SellController extends Controller
{
    protected $transactionUtil;
    protected $productUtil;
    protected $contactUtil;
    protected $taxUtil;

    public function __construct(
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        ContactUtil $contactUtil,
        TaxUtil $taxUtil
    ) {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->contactUtil = $contactUtil;
        $this->taxUtil = $taxUtil;
    }
}
```

**Do NOT introduce a new generic Service or Repository layer for business workflows.** Use Util classes. Existing thin infrastructure adapters may remain when they only wrap integrations.

### 4.3 Extracting Logic

Extract to a Util class when:
- Same logic appears in 2+ controllers
- A controller method exceeds ~60 lines
- Logic needs independent testing

### 4.4 Permission Checks

```php
// In controller method:
if (!auth()->user()->can('sell.create')) {
    abort(403, 'Unauthorized action.');
}

// Or using respondUnauthorized:
if (!auth()->user()->can('sell.create')) {
    return $this->respondUnauthorized(__('messages.unauthorized_action'));
}
```

---

## 5. Utils Layer

### 5.1 Utils Overview

| Util Class | Responsibility |
|---|---|
| `BusinessUtil` | Business settings, locations, financial year |
| `TransactionUtil` | Sales, purchases, payments, returns |
| `ProductUtil` | Products, variations, stock, pricing |
| `ContactUtil` | Suppliers, customers, ledger |
| `TaxUtil` | Tax rates, tax calculations |
| `AccountTransactionUtil` | Chart of accounts, account transactions |
| `CashRegisterUtil` | Cash register open/close, transactions |
| `NotificationUtil` | Email/SMS notifications |
| `ModuleUtil` | Module enable/disable checks |
| `RestaurantUtil` | Restaurant-specific features |

### 5.2 Creating a New Util

```php
<?php

namespace App\Utils;

use App\MyModel;

class MyFeatureUtil extends Util
{
    /**
     * Brief description.
     *
     * @param int $business_id
     * @param array $params ['key' => value, ...]
     * @return mixed
     */
    public function doSomething(int $business_id, array $params)
    {
        // Always scope by business_id
        return MyModel::where('business_id', $business_id)
            ->where('status', $params['status'])
            ->get();
    }
}
```

### 5.3 Session Data (Multi-Tenant Context)

The `SetSessionData` middleware sets these session keys on every authenticated request:

```php
session('user')              // array: id, surname, first_name, last_name, email, business_id, language
session('business')          // Business model instance
session('currency')          // array: id, code, symbol, thousand_separator, decimal_separator
session('financial_year')    // array: start, end dates
```

**Access in controllers:**
```php
$business_id = request()->session()->get('user.business_id');
$business = request()->session()->get('business');
$currency = request()->session()->get('currency');
```

---

## 6. Blade Views Conventions

### 6.0 Core UI (Metronic Project-Wide)

- **All Blade views** (core and modules) use **Metronic 8.3.3**. Use the HTML reference in `Modules/ProjectX/Resources/html/` and the patterns in `ai/ui-components.md`.
- **Legacy core views** that still use old Bootstrap/AdminLTE: when touching them, migrate to Metronic or preserve only if the task explicitly excludes migration.

### 6.1 View Directory Structure

Core views: `resources/views/[section]/[action].blade.php`

Common patterns:
```
resources/views/
├── layouts/
│   ├── app.blade.php          ← Main layout
│   └── partials/
│       ├── header.blade.php
│       └── sidebar.blade.php
├── components/                ← Reusable Blade components
│   ├── datatable-card.blade.php
│   ├── widget.blade.php
│   ├── filters.blade.php
│   └── avatar.blade.php
├── [section]/
│   ├── index.blade.php        ← List page
│   ├── create.blade.php       ← Create form
│   ├── edit.blade.php         ← Edit form
│   ├── show.blade.php         ← Detail view
│   └── partials/              ← Section-specific partials
│       └── [partial].blade.php
```

Module views: `Modules/[ModuleName]/Resources/views/`

### 6.2 Layout Extension

```blade
@extends('layouts.app')

@section('title', __('lang_v.page_title'))

@section('content')
    <!-- Main content using Metronic card/components per ai/ui-components.md -->
@endsection

@section('javascript')
    <script>
        // Page-specific JS
    </script>
@endsection
```

### 6.3 View Data — No @php Defaults or Calculations

All data the view needs (including defaults and derived values) must be provided by the Controller, relevant Util/presenter logic, ViewModel when already established, or view composer. Blade must **not** use `@php` to:

- Assign or default variables (e.g. `$x = $x ?? config(...)` or `$config = helper()`).
- Compute values (e.g. precision, step, min for number inputs from a config array).

For module-specific values (e.g. ProjectX number-format: `projectxCurrencyPrecision`, `projectxCurrencyStep`, `projectxQuantityMin`), prepare them in the controller or in the relevant Util / view composer and pass them to the view. The view only uses the passed variables (e.g. `{{ $projectxCurrencyStep }}`).

### 6.4 Custom Blade Directives

Registered in `AppServiceProvider`:

```blade
@num_format($number)           ← Format number per business settings
@format_currency($amount)      ← Format with currency symbol
@format_date($date)            ← Format date per business settings
@show_wt($amount)              ← Show amount with tax
@show_profit($cost, $price)    ← Show profit margin
```

### 6.5 Translation

```blade
@lang('lang_v.key')
__('lang_v.key')
__('messages.key')
__('essentials::lang.key')    ← Module-specific translations
```

### 6.6 Module Checks

```blade
@if (Module::has('Essentials') && Module::isEnabled('Essentials'))
    <!-- Essentials-specific content -->
@endif
```

---

## 7. Form Request Validation

Use Form Request classes for all validation.

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can('sell.create');
    }

    public function rules()
    {
        return [
            'contact_id'   => 'required|integer',
            'location_id'  => 'required|integer',
            'transaction_date' => 'required|date',
            'products'     => 'required|array|min:1',
        ];
    }
}
```

---

## 8. Route Conventions

### 8.1 Route Files

| File | Purpose |
|---|---|
| `routes/web.php` | Core web routes (all require `auth` + `SetSessionData` middleware) |
| `routes/api.php` | API routes (Laravel Passport) |
| `Modules/*/routes/web.php` | Module-specific web routes |
| `Modules/*/routes/api.php` | Module-specific API routes |

### 8.2 Route Naming

```php
// Resource routes
Route::resource('contacts', ContactController::class);
// Generates: contacts.index, contacts.create, contacts.store, contacts.show, contacts.edit, contacts.update, contacts.destroy

// Named routes
Route::get('/sell', [SellController::class, 'index'])->name('sell.index');
```

### 8.3 Route Protection

```php
Route::group(['middleware' => ['auth', 'SetSessionData']], function () {
    // All authenticated routes here
    Route::get('/home', [HomeController::class, 'index'])->name('home');
});
```

---

## 9. Response Helpers

For AJAX controllers, always use the base controller helpers:

```php
// Success
return $this->respondSuccess(__('lang_v.success'));

// Success with data
return $this->respondSuccess(__('lang_v.success'), ['data' => $items]);

// Error
return $this->respondWithError(__('messages.something_went_wrong'));

// Unauthorized
return $this->respondUnauthorized(__('messages.unauthorized_action'));

// Exception handler
try {
    // risky operation
} catch (\Exception $e) {
    \Log::emergency('File: '.$e->getFile().' Line: '.$e->getLine().' Message: '.$e->getMessage());
    return $this->respondWentWrong($e);
}
```

---

## 10. Global Helpers

Defined in `app/Http/helpers.php` (autoloaded via composer):

| Function | Purpose |
|---|---|
| `humanFilesize($size, $precision)` | Formats file size bytes to human readable |
| `isFileImage($filename)` | Checks if file is an image by extension |
| `pos_boot(...)` | License validation (do not modify) |

Check this file before writing a new utility function.

---

## 11. nwidart Module Conventions

### 11.1 Module Structure

```
Modules/MyModule/
├── Config/
├── Console/
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── Entities/          ← Module-specific models
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Providers/
│   └── MyModuleServiceProvider.php
├── Resources/
│   ├── assets/
│   ├── lang/
│   └── views/
├── Routes/
│   ├── api.php
│   └── web.php
└── module.json
```

### 11.2 Module Status

Module enable/disable is tracked in `modules_statuses.json` (root).

### 11.3 Checking Module Availability in Controllers

```php
if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'crm')) {
    // CRM is enabled
}
```

### 11.4 Cross-Module Access

Never reach directly into another module's DB tables.
Access functionality through that module's Util or controller action.

---

## 12. Coding Standards Summary

| Rule | Convention |
|---|---|
| Models location | `app/ModelName.php` (not `app/Models/`) |
| Model namespace | `namespace App;` |
| Model mass-assignment | `protected $guarded = ['id'];` |
| Business logic | `app/Utils/*Util.php` — never in controllers directly |
| New business logic | Add method to existing Util, or create new `*Util` extending `Util` |
| Validation | Always use Form Request classes |
| JSON responses | Always use `respondSuccess()` / `respondWithError()` / `respondWentWrong()` |
| Tenant scoping | Every query must filter `->where('business_id', $business_id)` |
| Permissions | Check `auth()->user()->can('permission.name')` before every mutation |
| Exceptions | Wrap in try/catch, log with `\Log::emergency()`, return `respondWentWrong($e)` |
| New modules | Use `php artisan module:make ModuleName` scaffolding |
| Translations | Use `__('lang_v.key')` — never hardcode UI strings |
