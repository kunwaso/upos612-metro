# Security & Authentication

This document covers every security boundary, auth guard, middleware, and permission
pattern in this project. Read this before touching any route, controller, or middleware.

---

## 1. Authentication Guards

**Config:** `config/auth.php`

| Guard | Driver | Provider | Purpose |
|---|---|---|---|
| `web` (default) | `session` | `users` table | Admin/staff login |
| `api` | `passport` | `users` table | Mobile app / API clients |
| `customer` | `session` | `contacts` table | Ecommerce customer portal |

### 1.1 Web Guard (Admin)

Standard Laravel session auth. Users log in via `POST /login`.

After successful login:
- Session is regenerated (prevents fixation)
- `SetSessionData` middleware populates business context into session

### 1.2 API Guard (Passport)

Laravel Passport with OAuth2 tokens.

API routes: `routes/api.php` + `Modules/*/Routes/api.php`

All API routes are behind `auth:api` middleware.

### 1.3 Customer Guard

Used by the Ecommerce module. Customers authenticate against the `contacts` table.

---

## 2. Middleware Stack

### 2.1 Core Middleware (`app/Http/Middleware/`)

| Middleware | Route Alias | Purpose |
|---|---|---|
| `Authenticate` | `auth` | Redirect unauthenticated users to login |
| `SetSessionData` | `SetSessionData` | Set business context in session for multi-tenancy |
| `Timezone` | (registered globally) | Set app timezone from business settings |
| `Language` | (registered) | Set app locale from user settings |
| `CheckUserLogin` | — | Additional login state verification |
| `Superadmin` | `Superadmin` | Restrict routes to superadmin users only |
| `IsInstalled` | — | Block access if app not installed |
| `EcomApi` | `EcomApi` | Ecommerce-specific API authentication |
| `AdminSidebarMenu` | — | Builds the sidebar menu for the current user |
| `VerifyCsrfToken` | — | CSRF protection on all state-changing web requests |
| `RedirectIfAuthenticated` | `guest` | Redirect already-authenticated users away from login |

### 2.2 Standard Route Protection Pattern

All admin routes require both `auth` AND `SetSessionData`:

```php
Route::group(['middleware' => ['auth', 'SetSessionData']], function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    // ... all other admin routes
});
```

Never expose admin routes without both middleware.

### 2.3 Session Data Set by SetSessionData

```php
session('user')       // ['id', 'business_id', 'first_name', 'last_name', 'surname', 'email', 'language']
session('business')   // Business model instance
session('currency')   // ['id', 'code', 'symbol', 'thousand_separator', 'decimal_separator']
session('financial_year') // ['start', 'end']
```

If any of these are missing, downstream business logic will fail.

---

## 3. Permission System (Spatie)

**Package:** `spatie/laravel-permission` v5.x

### 3.1 Role Naming Convention

Roles are scoped to a business using a name pattern:

```
RoleName#business_id
```

Examples:
- `Admin#1` — Admin role for business ID 1
- `Cashier#3` — Cashier role for business ID 3
- `Purchase Manager#2` — Purchase Manager for business ID 2

### 3.2 Checking Permissions in Controllers

```php
// Check a single permission
if (!auth()->user()->can('sell.create')) {
    return $this->respondUnauthorized('Unauthorized action.');
}

// Check role
if (auth()->user()->hasRole('Admin#' . $business_id)) {
    // Is admin for this business
}

// Check any of multiple permissions
if (!auth()->user()->hasAnyPermission(['sell.view', 'sell.view_own'])) {
    abort(403);
}
```

### 3.3 Checking Permissions in Blade

```blade
@can('sell.create')
    <a href="{{ route('sell.create') }}" class="...">Add Sale</a>
@endcan

@if(auth()->user()->can('product.update'))
    <button ...>Edit</button>
@endif

@if(auth()->user()->hasRole('Admin#' . session('user.business_id')))
    <!-- Admin-only UI -->
@endif
```

### 3.4 Common Permission Names

Permissions follow pattern `module.action`:

| Permission | Description |
|---|---|
| `sell.view` | View all sales |
| `sell.view_own` | View own sales only |
| `sell.create` | Create new sale |
| `sell.update` | Edit sales |
| `sell.delete` | Delete sales |
| `purchase.view` | View purchases |
| `purchase.create` | Create purchase |
| `contact.view` | View contacts |
| `contact.create` | Add contacts |
| `product.view` | View products |
| `product.create` | Add products |
| `product.update` | Edit products |
| `report.view` | View reports |
| `account.access` | Access accounts |
| `expense.view` | View expenses |
| `expense.create` | Create expense |
| `user.view` | View users |
| `user.create` | Create users |
| `role.view` | View roles |
| `business_settings.access` | Modify business settings |
| `opening_stock.access` | Access opening stock |
| `cash_register.view` | View cash registers |

### 3.5 Permission Caching

Spatie caches permissions. After any permission/role change, reset the cache:

```bash
php artisan permission:cache-reset
```

Or in code:
```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

## 4. Tenant Isolation (Critical Security)

### 4.1 The Rule

Every query on tenant data **must** include `business_id` filtering.

```php
// CORRECT
$products = Product::where('business_id', $business_id)->get();

// WRONG — leaks data across all tenants
$products = Product::all();
```

### 4.2 Getting business_id

```php
// In controller:
$business_id = request()->session()->get('user.business_id');

// In Util class:
$business_id = $data['business_id']; // passed from controller
```

### 4.3 Verifying Resource Ownership

Before showing/editing any resource, verify it belongs to the current business:

```php
$contact = Contact::where('business_id', $business_id)
                  ->findOrFail($id);
// findOrFail implicitly prevents accessing another tenant's record
// if id doesn't exist in this business, it throws 404
```

Never do:
```php
$contact = Contact::findOrFail($id); // ← INSECURE — no tenant check
```

---

## 5. CSRF Protection

All state-changing web requests require CSRF. In Blade forms:

```blade
<form method="POST" action="{{ route('resource.store') }}">
    @csrf
    ...
</form>
```

For AJAX POST/PUT/DELETE:
```javascript
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

The meta tag is in the layout `<head>`:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

CSRF is bypassed for routes in `VerifyCsrfToken::$except` array (API routes excluded by design).

---

## 6. Input Validation

**All user input must be validated.** Use Form Request classes (see `ai/laravel-conventions.md`).

### 6.1 Output Escaping

```blade
{{ $variable }}     ← SAFE — auto-escaped HTML
{!! $variable !!}   ← DANGEROUS — only for trusted, sanitized content
```

Never use `{!! !!}` for user-submitted content.

### 6.2 Query Parameter Sanitization

Never use raw request input in queries:

```php
// WRONG
DB::select("SELECT * FROM contacts WHERE name = '{$request->name}'");

// CORRECT — Eloquent/Query Builder with binding
Contact::where('name', $request->name)->get();

// CORRECT — raw query with binding
DB::select("SELECT * FROM contacts WHERE name = ?", [$request->name]);
```

---

## 7. File Upload Security

When handling file uploads:

```php
// Validate MIME type and size in Form Request
public function rules()
{
    return [
        'document' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:2048', // 2MB max
    ];
}

// Store outside web root (or in storage/app/uploads)
$path = $request->file('document')->store('documents', 'local');

// For public access, store in storage/app/public
$path = $request->file('image')->store('images', 'public');
```

Never store uploaded files directly in `public/` without validation.

---

## 8. Rate Limiting

For login and sensitive routes, throttle middleware is applied:

```php
Route::post('/login', [LoginController::class, 'login'])
     ->middleware('throttle:10,1'); // 10 attempts per minute
```

The API routes use `throttle:60,1` by default via the `api` middleware group.

---

## 9. API Security (Passport)

### 9.1 Token Creation

```php
// Personal access token (for testing/admin)
$token = $user->createToken('token-name')->accessToken;

// Password grant (mobile apps)
// Configured via: php artisan passport:client --password
```

### 9.2 API Authentication in Controllers

```php
// Protect API route
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
```

### 9.3 Revoking Tokens

```php
auth()->user()->tokens()->delete(); // revoke all tokens
```

---

## 10. Superadmin Guard

A separate superadmin system exists in the `Superadmin` module.

Superadmin routes are protected by the `Superadmin` middleware:

```php
Route::middleware(['auth', 'Superadmin'])->group(function () {
    // Superadmin-only routes
});
```

The `Superadmin` middleware checks if the authenticated user has the system-level superadmin flag — separate from business-level admin roles.

---

## 10.1 AI Chat Credential Hard Deny (Aichat)

For every Aichat prompt context, tool payload, and audit metadata:

- Never include credential/auth fields in model-visible context.
- Use strict serializer allowlists for chat payloads; deny unmapped fields by default.
- Redact blocked keys and token patterns before logging/auditing.

At minimum, hard-deny these classes of data:

- Passwords and password hashes
- Remember/session tokens and raw auth artifacts
- API keys, OAuth access/refresh tokens, Passport secrets
- 2FA secrets and recovery codes
- Telegram bot tokens and LLM provider keys
- Encryption/private keys and equivalent secrets

This requirement applies to both web and Telegram chat paths.

---

## 11. Security Checklist Before Shipping

Before marking any feature complete, verify:

- [ ] All routes behind `auth` + `SetSessionData` middleware
- [ ] All queries include `->where('business_id', $business_id)`
- [ ] All create/update/delete actions check `auth()->user()->can('permission')`
- [ ] All forms have `@csrf`
- [ ] No raw SQL with unsanitized user input
- [ ] Output uses `{{ }}` not `{!! !!}` for user data
- [ ] File uploads validate MIME type and size
- [ ] No hardcoded credentials or API keys in code
- [ ] Error messages don't leak system details in production

---

## 12. Common Security Mistakes to Avoid

| Mistake | Correct Pattern |
|---|---|
| `Contact::findOrFail($id)` without business_id check | `Contact::where('business_id', $business_id)->findOrFail($id)` |
| Skipping `@csrf` on forms | Always include `@csrf` |
| Using `{!! $user_input !!}` | Use `{{ $user_input }}` |
| Raw SQL: `"WHERE name = '$input'"` | Use Eloquent or `DB::select('...?', [$input])` |
| Returning exception details in production | Use `respondWentWrong($e)` which respects `APP_DEBUG` |
| Skipping permission check for mutations | Always check `auth()->user()->can()` before store/update/destroy |
