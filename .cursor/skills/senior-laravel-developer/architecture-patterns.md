# Architecture Patterns Reference

## Current Architecture: Utils Pattern

This codebase uses a **Utils-based architecture** where business logic lives in `app/Utils/*Util.php` classes that extend a common `Util` base class.

### When to Create a New Util

Create a new Util when:
- A new domain area emerges (e.g., `ReportUtil`, `NotificationUtil`)
- An existing Util exceeds ~500 lines — split by sub-domain
- Logic is shared across multiple controllers or modules

```php
namespace App\Utils;

class ReportUtil extends Util
{
    /**
     * Generate sales summary for a business within a date range.
     *
     * @param int $business_id
     * @param array $filters ['start_date' => Carbon, 'end_date' => Carbon, 'location_id' => int|null]
     * @return array ['total_sales' => float, 'total_transactions' => int, ...]
     */
    public function getSalesSummary(int $business_id, array $filters): array
    {
        // Implementation
    }
}
```

### Util Method Guidelines

- Always accept `$business_id` as the first parameter for tenant-scoped operations
- Document array parameters with PHPDoc `@param array $filters` showing expected keys
- Return typed values — avoid returning mixed types from the same method
- Keep methods focused: one method = one responsibility
- Name clearly: `getXxx` for reads, `createXxx` for inserts, `updateXxx` for modifications, `deleteXxx` for removals

---

## Module Architecture (nwidart/laravel-modules)

### Module Structure

```
Modules/
└── ModuleName/
    ├── Config/
    ├── Database/
    │   ├── Migrations/
    │   └── Seeders/
    ├── Entities/         # Models
    ├── Http/
    │   ├── Controllers/
    │   ├── Middleware/
    │   └── Requests/
    ├── Providers/
    ├── Resources/
    │   └── views/
    ├── Routes/
    │   ├── web.php
    │   └── api.php
    └── module.json
```

### Inter-Module Communication

**Do:**
- Expose a Util or service class that other modules can call
- Use Laravel events to notify other modules of state changes
- Define interfaces when a module needs to be swappable

**Don't:**
- Query another module's tables directly
- Call another module's controller methods
- Import another module's models without going through its API

```php
// GOOD: Module A fires an event, Module B listens
// In Module A:
event(new OrderPlaced($order));

// In Module B's listener:
class UpdateInventoryOnOrder
{
    public function handle(OrderPlaced $event)
    {
        // Update inventory using Module B's own models
    }
}
```

---

## Gradual Migration Strategies

### From Fat Controller to Util

**Step 1:** Identify a block of logic in a controller (20+ lines doing one thing).

**Step 2:** Extract to the appropriate Util with a descriptive method name.

**Step 3:** Replace the controller code with a Util call.

```php
// Before (in controller):
public function store(Request $request)
{
    // 40 lines of transaction creation logic...
}

// After:
public function store(StoreTransactionRequest $request)
{
    $data = $request->validated();
    $result = $this->transactionUtil->createTransaction($business_id, $data);
    return $this->respondSuccess($result);
}
```

### From Inline Validation to Form Requests

```bash
php artisan make:request StoreTransactionRequest
```

Move rules from `$request->validate([...])` in controllers to the Form Request's `rules()` method. Add `authorize()` to check permissions there too.

---

## Query Patterns

### Scoping by Business

Every query must be tenant-safe:

```php
// ALWAYS scope
Product::where('business_id', $business_id)->get();

// NEVER trust user-supplied IDs without verifying ownership
$product = Product::where('business_id', $business_id)
    ->findOrFail($request->product_id);
```

### Eloquent Scopes for Reuse

```php
// In the model
public function scopeForBusiness($query, $business_id)
{
    return $query->where('business_id', $business_id);
}

public function scopeActive($query)
{
    return $query->where('is_active', 1);
}

// Usage
Product::forBusiness($business_id)->active()->get();
```

### Complex Reporting Queries

For reports, use Query Builder over Eloquent when you need joins, aggregations, or subqueries:

```php
$report = DB::table('transactions')
    ->join('contacts', 'transactions.contact_id', '=', 'contacts.id')
    ->where('transactions.business_id', $business_id)
    ->whereBetween('transactions.transaction_date', [$start, $end])
    ->select([
        'contacts.name',
        DB::raw('SUM(transactions.final_total) as total'),
        DB::raw('COUNT(transactions.id) as count'),
    ])
    ->groupBy('contacts.id', 'contacts.name')
    ->orderByDesc('total')
    ->get();
```

Use `DB::` for reports — it's faster and clearer for aggregations. Use Eloquent for CRUD operations.
