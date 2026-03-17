# Performance Playbook

## Query Performance

### Identifying Slow Queries

```php
// Enable query logging in development
DB::enableQueryLog();

// ... your code ...

// Dump all queries with timing
dd(DB::getQueryLog());
```

For production, use Laravel Telescope or log slow queries via MySQL's slow query log.

### Index Strategy

Add indexes for:
- All `business_id` columns (tenant scoping)
- Foreign key columns (`*_id`)
- Columns used in `WHERE`, `ORDER BY`, `GROUP BY`
- Composite indexes for common query combinations

```php
// Migration for composite index
$table->index(['business_id', 'transaction_date'], 'idx_business_date');
$table->index(['business_id', 'contact_id', 'type'], 'idx_business_contact_type');
```

### Pagination

Never use `->get()` on unbounded queries in controllers. Always paginate:

```php
// For Blade views
$products = Product::forBusiness($business_id)->paginate(25);

// For DataTables (Yajra) — already handles pagination server-side
// Just ensure the base query is optimized
```

### Chunking Large Datasets

```php
// For batch processing (exports, reports, cleanup)
Transaction::where('business_id', $business_id)
    ->where('created_at', '<', now()->subYear())
    ->chunk(500, function ($transactions) {
        foreach ($transactions as $transaction) {
            // Process
        }
    });

// For memory-efficient iteration
Transaction::where('business_id', $business_id)
    ->cursor()
    ->each(function ($transaction) {
        // Process one at a time, low memory
    });
```

---

## Caching Strategy

### What to Cache

| Data | TTL | Invalidation |
|------|-----|--------------|
| Business settings | 1 hour | On settings update |
| Product counts/totals | 15 min | On product create/delete |
| Permission lists | Until change | On role/permission update |
| Tax rates | 1 hour | On tax rate update |
| Currency exchange rates | 6 hours | On scheduled refresh |

### Cache Key Convention

Use consistent, collision-free keys:

```php
// Pattern: {entity}_{business_id}_{identifier}
"business_settings_{$business_id}"
"product_count_{$business_id}_{$location_id}"
"tax_rates_{$business_id}"
```

### Cache Invalidation

```php
// In model observers or after mutations
Cache::forget("business_settings_{$business_id}");

// Or use tagged caches for bulk invalidation
Cache::tags(["business_{$business_id}"])->flush();
```

---

## DataTables Optimization (Yajra)

Since this project uses Yajra DataTables heavily:

```php
// GOOD: Efficient server-side query
return DataTables::of(
    Product::where('business_id', $business_id)
        ->with('category:id,name')
        ->select(['id', 'name', 'sku', 'price', 'category_id'])
)
->make(true);

// BAD: Loading all columns and relations
return DataTables::of(Product::where('business_id', $business_id)->get())
    ->make(true);
```

Rules:
- Select only columns needed for display
- Eager load only necessary relationships with column limits
- Let DataTables handle pagination (don't `->get()` first)
- Add indexes for sortable/searchable columns

---

## Queue Optimization

### What to Queue

- PDF generation (mPDF/DomPDF) — can take 2-10 seconds
- Excel exports (Maatwebsite) — memory-heavy
- Email/SMS notifications
- Payment gateway callbacks and reconciliation
- Bulk operations (price updates, inventory adjustments)

### Queue Configuration

```php
// For heavy jobs, use a dedicated queue
dispatch(new GenerateReport($params))->onQueue('reports');

// For time-sensitive notifications
dispatch(new SendTransactionSms($transaction))->onQueue('notifications');
```

### Job Best Practices

```php
class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 300;

    public function handle(): void
    {
        // Use business_id for tenant context — don't rely on session in jobs
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Report generation failed: {$exception->getMessage()}", [
            'business_id' => $this->business_id,
        ]);
    }
}
```

---

## Memory Management

### Large Exports

```php
// BAD: loads everything into memory
$data = Transaction::all();
return Excel::download(new TransactionsExport($data), 'transactions.xlsx');

// GOOD: use query-based export with chunking
class TransactionsExport implements FromQuery, WithChunkReading
{
    public function query()
    {
        return Transaction::where('business_id', $this->business_id);
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
```

### PDF Generation

mPDF and DomPDF are memory-hungry. For large documents:
- Generate in a queued job
- Limit rows per page
- Use simpler HTML/CSS (avoid complex tables)
- Set memory limit in the job: `ini_set('memory_limit', '512M')`
