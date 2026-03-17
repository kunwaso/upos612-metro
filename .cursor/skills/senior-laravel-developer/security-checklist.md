# Security Checklist

## Tenant Isolation (Critical)

This is a multi-tenant POS system. Data leakage between businesses is a **critical** vulnerability.

### Every Query Must Be Scoped

```php
// ALWAYS verify business ownership
$product = Product::where('business_id', $business_id)
    ->findOrFail($id);

// NEVER trust an ID from the request without scoping
// BAD:
$product = Product::findOrFail($request->product_id);

// GOOD:
$product = Product::where('business_id', $business_id)
    ->findOrFail($request->product_id);
```

### Relationships Must Be Verified

```php
// If loading a related model, verify it belongs to the same business
$transaction = Transaction::where('business_id', $business_id)->findOrFail($id);

// Even through relationships, verify:
$payment = $transaction->payments()->findOrFail($payment_id);
// This is safe because the transaction is already scoped to the business
```

### Checklist

- [ ] All controller queries include `business_id` filter
- [ ] Route model binding uses scoped queries (not global `findOrFail`)
- [ ] API endpoints verify resource ownership before returning data
- [ ] Reports and exports filter by business_id
- [ ] Background jobs receive and use business_id explicitly (not from session)

---

## Authentication & Authorization

### Permission Checks

```php
// In controllers — use Spatie's middleware or manual checks
public function edit($id)
{
    if (!auth()->user()->can('product.update')) {
        abort(403);
    }

    $product = Product::where('business_id', $business_id)->findOrFail($id);
    // ...
}

// In Blade
@can('product.update')
    <a href="{{ route('products.edit', $product->id) }}">Edit</a>
@endcan
```

### Checklist

- [ ] Every create/update/delete action checks permissions
- [ ] Permission names are consistent and documented
- [ ] Superadmin routes are protected by `superadmin` middleware
- [ ] API routes use `auth:api` (Passport) middleware
- [ ] Session is regenerated after login

---

## Input Validation

### Validate Everything

```php
// Prefer Form Requests over inline validation
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('product.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
```

### Common Validation Pitfalls

- **Unique rules without scoping**: `unique:products,sku` should be `unique:products,sku,NULL,id,business_id,{$business_id}` for multi-tenant
- **Missing numeric validation**: Prices, quantities should be `numeric|min:0`
- **File uploads without MIME check**: Always validate `mimes:` and `max:` size
- **Missing `exists:` rules**: Foreign key references should validate the record exists

---

## SQL Injection Prevention

```php
// SAFE: Eloquent and Query Builder use parameter binding
Product::where('name', 'like', '%' . $search . '%')->get();

// SAFE: Raw with bindings
DB::select('SELECT * FROM products WHERE business_id = ? AND name LIKE ?', [
    $business_id,
    '%' . $search . '%',
]);

// DANGEROUS: Raw string concatenation
DB::select("SELECT * FROM products WHERE name LIKE '%{$search}%'");

// DANGEROUS: orderBy with user input (not parameterized)
Product::orderBy($request->sort_column)->get();
// FIX: whitelist allowed columns
$allowed = ['name', 'price', 'created_at'];
$sort = in_array($request->sort_column, $allowed) ? $request->sort_column : 'name';
Product::orderBy($sort)->get();
```

---

## XSS Prevention

```php
// SAFE: Blade escapes by default
{{ $product->name }}

// DANGEROUS: Unescaped output
{!! $product->description !!}

// If you must render HTML, sanitize first:
{!! clean($product->description) !!}
// Use HTMLPurifier or similar
```

### Checklist

- [ ] All user-generated content uses `{{ }}` not `{!! !!}`
- [ ] Any `{!! !!}` usage is on trusted/sanitized content only
- [ ] User input is not reflected in JavaScript without encoding
- [ ] Custom Blade directives escape output appropriately

---

## File Upload Security

```php
// Validate on server side (never trust client-side validation)
$request->validate([
    'file' => 'required|file|mimes:pdf,xlsx,csv|max:10240', // 10MB
]);

// Store outside web root
$path = $request->file('file')->store('uploads', 'local');

// Generate safe filenames
$filename = Str::uuid() . '.' . $request->file('file')->extension();
```

### Checklist

- [ ] File types are validated server-side via MIME type
- [ ] File sizes are limited
- [ ] Files are stored outside the public web root
- [ ] Original filenames are not used (use UUID or hash)
- [ ] Image files are re-processed to strip EXIF/metadata if sensitive

---

## Rate Limiting

```php
// In RouteServiceProvider or route definition
Route::middleware('throttle:60,1')->group(function () {
    // 60 requests per minute
});

// Login route — more aggressive
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 attempts per minute

// API routes
Route::middleware(['auth:api', 'throttle:120,1'])->group(function () {
    // API endpoints
});
```

---

## Logging & Audit

This project uses Spatie Activity Log. Ensure sensitive operations are logged:

```php
activity()
    ->performedOn($product)
    ->causedBy(auth()->user())
    ->withProperties(['old' => $original, 'new' => $changes])
    ->log('updated');
```

### What to Log

- All create/update/delete operations on business data
- Login attempts (success and failure)
- Permission changes
- Payment transactions
- Data exports

### What NOT to Log

- Passwords or tokens (even hashed)
- Full credit card numbers
- Session tokens
