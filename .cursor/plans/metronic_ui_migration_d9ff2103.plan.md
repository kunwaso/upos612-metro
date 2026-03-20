---
name: Metronic UI Migration
overview: Migrate all 117+ legacy AdminLTE-style views in `resources/views` to Metronic 8.3.3, organized into 7 phases by module. Shared components are migrated first (Phase 0) for maximum leverage, then modules proceed in priority order.
todos:
  - id: phase0-widget
    content: Phase 0 — Migrate components/widget.blade.php to Metronic card card-flush structure
    status: completed
  - id: phase0-filters
    content: Phase 0 — Migrate components/filters.blade.php to Metronic collapsible card structure (remove @php mobile logic)
    status: completed
  - id: phase1-purchase-create
    content: Phase 1 — Migrate purchase/create.blade.php to Metronic toolbar + form-control-solid layout
    status: completed
  - id: phase1-purchase-edit
    content: Phase 1 — Migrate purchase/edit.blade.php to Metronic toolbar + form-control-solid layout
    status: completed
  - id: phase1-sell-index
    content: Phase 1 — Migrate sell/index.blade.php to Metronic toolbar + filter card + table card
    status: in_progress
  - id: phase1-sell-shipments
    content: Phase 1 — Migrate sell/shipments.blade.php to Metronic toolbar + table card
    status: pending
  - id: phase1-contact-index
    content: Phase 1 — Migrate contact/index.blade.php to Metronic toolbar + filter card + table card
    status: pending
  - id: phase1-contact-import
    content: Phase 1 — Migrate contact/import.blade.php to Metronic toolbar + card layout
    status: pending
  - id: phase1-contact-map
    content: Phase 1 — Migrate contact/contact_map.blade.php to Metronic toolbar + card layout
    status: pending
  - id: phase2-reports-batch1
    content: "Phase 2 — Migrate report/ batch 1: activity_log, contact, customer_group, expense_report, gst_purchase_report"
    status: pending
  - id: phase2-reports-batch2
    content: "Phase 2 — Migrate report/ batch 2: gst_sales_report, items_report, lot_report, product_purchase_report, product_sell_report"
    status: pending
  - id: phase2-reports-batch3
    content: "Phase 2 — Migrate report/ batch 3: product_stock_details, profit_loss, purchase_payment_report, purchase_report, purchase_sell"
    status: pending
  - id: phase2-reports-batch4
    content: "Phase 2 — Migrate report/ batch 4: register_report, sales_representative, sale_report, sell_payment_report, service_staff_report"
    status: pending
  - id: phase2-reports-batch5
    content: "Phase 2 — Migrate report/ batch 5: stock_adjustment_report, stock_expiry_report, stock_report, table_report, tax_report, trending_products"
    status: pending
  - id: phase3-expense
    content: Phase 3 — Migrate expense/index, create, edit, import and expense_category/index
    status: pending
  - id: phase3-product-special
    content: Phase 3 — Migrate product/add-selling-prices, bulk-edit, stock_history
    status: pending
  - id: phase3-simple-lists
    content: Phase 3 — Migrate brand, unit, tax_rate, variation, taxonomy, warranties, customer_group index pages
    status: pending
  - id: phase4-purchase-return
    content: Phase 4 — Migrate purchase_return/index, add, create, edit
    status: pending
  - id: phase4-sell-return
    content: Phase 4 — Migrate sell_return/index, add, tmp_create
    status: pending
  - id: phase4-purchase-order
    content: Phase 4 — Migrate purchase_order/index, create, edit
    status: pending
  - id: phase4-sales-order-stock
    content: Phase 4 — Migrate sales_order/index, stock_adjustment/index+create, stock_transfer/index+create+edit
    status: pending
  - id: phase5-accounting
    content: Phase 5 — Migrate account/index, show, cash_flow and account_reports/balance_sheet, payment_account_report, trial_balance
    status: pending
  - id: phase6-users-roles
    content: Phase 6 — Migrate manage_user/index+create+edit and role/index+create+edit
    status: pending
  - id: phase6-business-config
    content: Phase 6 — Migrate business_location, discount, selling_price_group, invoice_layout, invoice_scheme, types_of_service, sales_commission_agent, restaurant
    status: pending
  - id: phase6-tools
    content: Phase 6 — Migrate barcode, labels, backup, printer, cash_register, import_products, import_sales, opening_stock, purchase_requisition, tax_group
    status: pending
isProject: false
---

# Metronic UI Full Migration Plan

## Architecture Pattern (applied to every page)

Every migrated page follows this canonical structure, referencing `public/html/apps/ecommerce/sales/listing.html` and `public/html/toolbars.html`:

```blade
{{-- Toolbar + Breadcrumb --}}
<div id="kt_toolbar" class="toolbar py-3 py-lg-5">
    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
        <div class="page-title d-flex flex-column align-items-start me-3 py-2 gap-2">
            <h1 class="d-flex text-dark fw-bold fs-3 mb-0">{{ __('...') }}</h1>
            <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-7">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('home.home') }}</a></li>
                <li class="breadcrumb-item text-gray-900">{{ __('...') }}</li>
            </ul>
        </div>
        {{-- toolbar action button goes here --}}
    </div>
</div>
{{-- Content --}}
<div class="d-flex flex-column-fluid align-items-start container-xxl">
    <div class="content flex-row-fluid" id="kt_content">
        {{-- filter card, main card --}}
    </div>
</div>
```

- Filter blocks → `card card-flush mb-5` with collapse toggle (`btn btn-light-primary btn-sm`)
- Data tables → `table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4`
- Action buttons → `btn btn-primary` with `ki-duotone ki-plus` icons
- Form inputs → `form-control form-control-solid` / `form-select`
- `<section class="content-header">` and `<section class="content">` → replaced entirely

---

## Phase 0 — Shared Components (max leverage, do first)

Migrating these 2 files upgrades every page that uses `@component(...)` automatically.

**Files changed:**

- `[resources/views/components/widget.blade.php](resources/views/components/widget.blade.php)`
- `[resources/views/components/filters.blade.php](resources/views/components/filters.blade.php)`

`**widget.blade.php`** → emit `card card-flush` structure:

```blade
<div class="card card-flush {{ $class ?? '' }}" @if(!empty($id)) id="{{ $id }}" @endif>
    @if(!empty($title) || !empty($tool))
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <h3 class="card-title">{{ $title ?? '' }}</h3>
        @if(!empty($tool))
        <div class="card-toolbar">{{ $tool }}</div>
        @endif
    </div>
    @endif
    <div class="card-body pt-0">{{ $slot }}</div>
</div>
```

`**filters.blade.php**` → emit collapsible card:

```blade
<div class="card card-flush mb-5" id="filter_card">
    <div class="card-header cursor-pointer" data-bs-toggle="collapse" data-bs-target="#collapseFilter">
        <h3 class="card-title">
            <i class="ki-duotone ki-filter fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
            {{ $title ?? __('report.filters') }}
        </h3>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-light-primary">
                <i class="ki-duotone ki-down fs-5"></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="collapseFilter">
        <div class="card-body pt-0">
            <div class="row g-3">{{ $slot }}</div>
        </div>
    </div>
</div>
```

Also remove the `@php if(isMobile())` block (mobile detection → controller/composer only, per constitution).

---

## Phase 1 — Purchases + Sales + Contact (core transactions)

> Note: `purchase/index.blade.php` already migrated in prior session.

**Purchase (2 form pages):**

- `[resources/views/purchase/create.blade.php](resources/views/purchase/create.blade.php)`
- `[resources/views/purchase/edit.blade.php](resources/views/purchase/edit.blade.php)`

**Sell (2 list/index pages):**

- `[resources/views/sell/index.blade.php](resources/views/sell/index.blade.php)`
- `[resources/views/sell/shipments.blade.php](resources/views/sell/shipments.blade.php)`

**Contact (3 pages):**

- `[resources/views/contact/index.blade.php](resources/views/contact/index.blade.php)`
- `[resources/views/contact/import.blade.php](resources/views/contact/import.blade.php)`
- `[resources/views/contact/contact_map.blade.php](resources/views/contact/contact_map.blade.php)`

Each task: replace `content-header` + `content` sections → toolbar + content pattern above; form inputs → `form-control-solid`.

---

## Phase 2 — Reports (26 pages)

All files in `[resources/views/report/](resources/views/report/)` that still use legacy layout:

- `activity_log.blade.php`, `contact.blade.php`, `customer_group.blade.php`
- `expense_report.blade.php`, `gst_purchase_report.blade.php`, `gst_sales_report.blade.php`
- `items_report.blade.php`, `lot_report.blade.php`, `product_purchase_report.blade.php`
- `product_sell_report.blade.php`, `product_stock_details.blade.php`, `profit_loss.blade.php`
- `purchase_payment_report.blade.php`, `purchase_report.blade.php`, `purchase_sell.blade.php`
- `register_report.blade.php`, `sales_representative.blade.php`, `sale_report.blade.php`
- `sell_payment_report.blade.php`, `service_staff_report.blade.php`
- `stock_adjustment_report.blade.php`, `stock_expiry_report.blade.php`, `stock_report.blade.php`
- `table_report.blade.php`, `tax_report.blade.php`, `trending_products.blade.php`

Pattern is identical for all (filter card + data table card). Group into batches of 5 per task.

---

## Phase 3 — Master Data (Products, Expenses, Brands, Units, Taxes)

**Expense (4 pages):**

- `[resources/views/expense/index.blade.php](resources/views/expense/index.blade.php)`
- `[resources/views/expense/create.blade.php](resources/views/expense/create.blade.php)`
- `[resources/views/expense/edit.blade.php](resources/views/expense/edit.blade.php)`
- `[resources/views/expense/import.blade.php](resources/views/expense/import.blade.php)`
- `[resources/views/expense_category/index.blade.php](resources/views/expense_category/index.blade.php)`

**Product pages (3 special pages):**

- `[resources/views/product/add-selling-prices.blade.php](resources/views/product/add-selling-prices.blade.php)`
- `[resources/views/product/bulk-edit.blade.php](resources/views/product/bulk-edit.blade.php)`
- `[resources/views/product/stock_history.blade.php](resources/views/product/stock_history.blade.php)`

**Simple list pages (1 file each):**

- `brand/index.blade.php`, `unit/index.blade.php`, `tax_rate/index.blade.php`
- `variation/index.blade.php`, `taxonomy/index.blade.php`
- `warranties/index.blade.php`, `customer_group/index.blade.php`

---

## Phase 4 — Returns, Orders, Stock Movements

**Purchase Return (4 pages):**

- `purchase_return/index.blade.php`, `add.blade.php`, `create.blade.php`, `edit.blade.php`

**Sell Return (3 pages):**

- `sell_return/index.blade.php`, `add.blade.php`, `tmp_create.blade.php`

**Purchase Order (3 pages):**

- `purchase_order/index.blade.php`, `create.blade.php`, `edit.blade.php`

**Sales Order (1 page):**

- `sales_order/index.blade.php`

**Stock Adjustment (2 pages):**

- `stock_adjustment/index.blade.php`, `create.blade.php`

**Stock Transfer (3 pages):**

- `stock_transfer/index.blade.php`, `create.blade.php`, `edit.blade.php`

---

## Phase 5 — Accounting

- `[resources/views/account/index.blade.php](resources/views/account/index.blade.php)`
- `[resources/views/account/show.blade.php](resources/views/account/show.blade.php)`
- `[resources/views/account/cash_flow.blade.php](resources/views/account/cash_flow.blade.php)`
- `[resources/views/account_reports/balance_sheet.blade.php](resources/views/account_reports/balance_sheet.blade.php)`
- `[resources/views/account_reports/payment_account_report.blade.php](resources/views/account_reports/payment_account_report.blade.php)`
- `[resources/views/account_reports/trial_balance.blade.php](resources/views/account_reports/trial_balance.blade.php)`

---

## Phase 6 — Settings & Configuration

**Users & Roles (6 pages):**

- `manage_user/index.blade.php`, `create.blade.php`, `edit.blade.php`
- `role/index.blade.php`, `create.blade.php`, `edit.blade.php`

**Business Config (simple list pages):**

- `business_location/index.blade.php`, `discount/index.blade.php`
- `selling_price_group/index.blade.php`, `selling_price_group/update_product_price.blade.php`
- `invoice_layout/index.blade.php`, `create.blade.php`, `edit.blade.php`
- `invoice_scheme/index.blade.php`, `types_of_service/index.blade.php`
- `sales_commission_agent/index.blade.php`, `restaurant/index.blade.php`

**Tools & Utilities:**

- `barcode/index.blade.php`, `create.blade.php`, `edit.blade.php`
- `labels/show.blade.php`, `backup/index.blade.php`
- `printer/index.blade.php`, `create.blade.php`, `edit.blade.php`
- `cash_register/index.blade.php`, `create.blade.php`
- `import_products/index.blade.php`, `import_sales/index.blade.php`, `preview.blade.php`
- `opening_stock/add.blade.php`, `purchase_requisition/index.blade.php`, `create.blade.php`
- `tax_group/index.blade.php`

---

## Per-Task Execution Rules (for the coding agent)

1. Read the target Blade file fully before editing.
2. Read the closest HTML reference in `public/html/` (listing → `apps/ecommerce/sales/listing.html`, form → `forms.html`, account → `apps/invoices/`).
3. Replace `<section class="content-header">` → toolbar block.
4. Replace `<section class="content">` → content container block.
5. Keep all existing `@component` references — Phase 0 already upgraded them.
6. Replace `tw-dw-btn ... tw-bg-gradient-to-r` buttons → `btn btn-primary` with `ki-duotone ki-plus`.
7. Replace inline SVG tabler icons → `ki-duotone` equivalents.
8. Replace `table-bordered table-striped` → `table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4`.
9. Run `ReadLints` after each file edit.
10. No business logic, no `@php` variable defaulting — data comes from controller.

