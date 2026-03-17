# Database Map

This document maps every Eloquent model to its table, key fields, relationships,
and important conventions. Read this before writing any query, migration, or model code.

---

## Critical Rule: Always Scope by `business_id`

Every table that stores tenant data has a `business_id` column.
**Every query on tenant data must include:** `->where('business_id', $business_id)`

Forgetting `business_id` is a data leak between tenants. This is a critical security issue.

---

## 1. Core Entities

### 1.1 Business (`app/Business.php`)

**Table:** `business`

| Key Fields | Description |
|---|---|
| `id` | Primary key |
| `name` | Business name |
| `currency_id` | FK to currencies |
| `tax_number_1`, `tax_number_2` | VAT / Tax IDs |
| `default_sales_tax` | Default tax rate ID |
| `start_date` | Financial year start |
| `financial_year_start_month` | 1–12 |
| `enabled_modules` | JSON — enabled features |
| `fy_start_month` | Financial year start month |
| `date_format`, `time_format` | Display formats |
| `currency_precision`, `quantity_precision` | Decimal places |

**Relationships:**
- `currency()` → `belongsTo(Currency)`
- `locations()` → `hasMany(BusinessLocation)`
- `tax_rates()` → `hasMany(TaxRate)`
- `users()` → `hasMany(User)`

---

### 1.2 User (`app/User.php`)

**Table:** `users`

| Key Fields | Description |
|---|---|
| `id` | Primary key |
| `first_name`, `last_name`, `surname` | Name parts |
| `email` | Unique login |
| `business_id` | FK to business |
| `language` | UI language code |
| `user_type` | `user` or `admin` (superadmin context) |
| `allow_login` | Boolean — can this user log in |
| `max_sales_discount_percent` | Max discount they can give |
| `crm_contact_id` | FK to CRM contact if linked |
| `sales_commission_percentage` | Commission % |

**Relationships:**
- `business()` → `belongsTo(Business)`
- `roles()` → Spatie roles (many-to-many via `model_has_roles`)
- `contactAccess()` → `hasMany(UserContactAccess)`

**Notes:**
- Roles follow pattern `RoleName#business_id` (e.g. `Admin#1`, `Cashier#1`)
- Password reset uses standard Laravel flow

---

### 1.3 Contact (`app/Contact.php`)

**Table:** `contacts`

| Key Fields | Description |
|---|---|
| `id` | Primary key |
| `business_id` | Tenant FK |
| `type` | `customer`, `supplier`, `both` |
| `name` | Display name |
| `company_name` | Company |
| `email` | Contact email |
| `mobile` | Phone |
| `tax_number` | VAT/Tax ID |
| `contact_id` | Custom reference ID |
| `balance` | Running balance |
| `credit_limit` | Credit limit |
| `customer_group_id` | FK to customer_groups |
| `is_default` | Boolean — default walk-in customer |
| `contact_status` | `active` / `inactive` |

**Relationships:**
- `business()` → `belongsTo(Business)`
- `customerGroup()` → `belongsTo(CustomerGroup)`
- `transactions()` → `hasMany(Transaction, 'contact_id')`

---

### 1.4 Product (`app/Product.php`)

**Table:** `products`

| Key Fields | Description |
|---|---|
| `id` | Primary key |
| `business_id` | Tenant FK |
| `name` | Product name |
| `sku` | Stock keeping unit |
| `barcode_type` | Barcode symbology |
| `unit_id` | FK to units |
| `category_id` | FK to categories |
| `brand_id` | FK to brands |
| `tax` | Tax rate ID |
| `tax_type` | `inclusive` / `exclusive` |
| `type` | `single` / `variable` / `combo` |
| `enable_stock` | Boolean |
| `alert_quantity` | Low stock alert level |
| `expiry_period`, `expiry_period_type` | Expiry tracking |
| `selling_price` | Default sell price |
| `purchase_price` | Default purchase price |
| `weight` | Product weight |
| `is_inactive` | Soft disable |

**Relationships:**
- `variations()` → `hasMany(Variation)`
- `unit()` → `belongsTo(Unit)`
- `category()` → `belongsTo(Category)`
- `brand()` → `belongsTo(Brands)`
- `tax()` → `belongsTo(TaxRate, 'tax')`

---

### 1.5 Transaction (`app/Transaction.php`)

**Table:** `transactions` — the central table of the entire system

| Key Fields | Description |
|---|---|
| `id` | Primary key |
| `business_id` | Tenant FK |
| `type` | See transaction types below |
| `status` | See transaction statuses below |
| `contact_id` | FK to contacts |
| `location_id` | FK to business_locations |
| `invoice_no` | Invoice reference number |
| `ref_no` | Reference number |
| `transaction_date` | Date of transaction |
| `total_before_tax` | Subtotal before tax |
| `tax_id` | FK to tax_rates |
| `tax_amount` | Calculated tax |
| `discount_type` | `fixed` / `percentage` |
| `discount_amount` | Discount value |
| `final_total` | Grand total |
| `payment_status` | `paid` / `partial` / `due` |
| `additional_notes` | Notes |
| `shipping_details` | Shipping info |
| `shipping_charges` | Shipping cost |
| `exchange_rate` | For multi-currency |
| `is_direct_sale` | Boolean — POS direct sale |
| `created_by` | User ID |
| `sub_status` | Sub-status field |
| `round_off_amount` | Rounding adjustment |
| `sales_order_ids` | JSON array |
| `purchase_order_ids` | JSON array |

**Transaction Types:**
```
purchase | sell | expense | stock_adjustment | sell_transfer
purchase_transfer | opening_stock | sell_return | opening_balance
purchase_return | payroll | expense_refund | sales_order | purchase_order
```

**Transaction Statuses:**
```
received | pending | ordered | draft | final | in_transit | completed
```

**Relationships:**
- `purchase_lines()` → `hasMany(PurchaseLine)`
- `sell_lines()` → `hasMany(TransactionSellLine)`
- `payment_lines()` → `hasMany(TransactionPayment, 'transaction_id')`
- `contact()` → `belongsTo(Contact)`
- `location()` → `belongsTo(BusinessLocation)`
- `business()` → `belongsTo(Business)`
- `tax()` → `belongsTo(TaxRate, 'tax_id')`
- `stock_adjustment_lines()` → `hasMany(StockAdjustmentLine)`
- `delivery_person_user()` → `belongsTo(User, 'delivery_person')`

---

### 1.6 TransactionSellLine (`app/TransactionSellLine.php`)

**Table:** `transaction_sell_lines`

| Key Fields | Description |
|---|---|
| `id` | Primary key |
| `transaction_id` | FK to transactions |
| `product_id` | FK to products |
| `variation_id` | FK to variations |
| `quantity` | Sold quantity |
| `unit_price` | Price per unit (before tax) |
| `unit_price_inc_tax` | Price per unit (inc tax) |
| `line_discount_type` | `fixed` / `percentage` |
| `line_discount_amount` | Line-level discount |
| `item_tax` | Tax amount for this line |
| `tax_id` | FK to tax_rates |

---

### 1.7 PurchaseLine (`app/PurchaseLine.php`)

**Table:** `purchase_lines`

| Key Fields | Description |
|---|---|
| `id` | Primary key |
| `transaction_id` | FK to transactions |
| `product_id` | FK to products |
| `variation_id` | FK to variations |
| `quantity` | Purchased quantity |
| `quantity_sold` | How much has been sold |
| `purchase_price` | Cost price |
| `purchase_price_inc_tax` | Cost price inc tax |
| `lot_number` | Lot/batch tracking |
| `mfg_date`, `exp_date` | Manufacturing/expiry dates |

---

### 1.8 TransactionPayment (`app/TransactionPayment.php`)

**Table:** `transaction_payments`

| Key Fields | Description |
|---|---|
| `id` | Primary key |
| `transaction_id` | FK to transactions |
| `amount` | Payment amount |
| `method` | `cash`, `card`, `cheque`, `bank_transfer`, etc. |
| `paid_on` | Payment date |
| `payment_for` | FK to contact (for contact payments) |
| `ref_number` | Reference |
| `note` | Payment note |
| `payment_type` | Type classification |

---

## 2. Inventory & Products

### 2.1 Variation (`app/Variation.php`)

**Table:** `variations`

Stores individual product variants (e.g. Size: Small, Color: Red).

| Key Fields | Description |
|---|---|
| `product_id` | FK to products |
| `name` | Variation name |
| `sub_sku` | Variant SKU |
| `default_sell_price` | Default selling price |
| `sell_price_inc_tax` | Selling price including tax |
| `default_purchase_price` | Default purchase cost |
| `purchase_price_inc_tax` | Purchase price including tax |
| `dpp_inc_tax` | Default purchase price inc tax |

### 2.2 VariationLocationDetails (`app/VariationLocationDetails.php`)

**Table:** `variation_location_details`

Tracks stock levels per variation per location.

| Key Fields | Description |
|---|---|
| `product_id` | FK to products |
| `product_variation_id` | FK to product_variations |
| `variation_id` | FK to variations |
| `location_id` | FK to business_locations |
| `qty_available` | Current stock on hand |

### 2.3 Category (`app/Category.php`)

**Table:** `categories`

Polymorphic — can categorize products, expenses, etc.

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `name` | Category name |
| `parent_id` | FK to self (hierarchical) |
| `category_type` | `product` / `expense` / etc. |
| `short_code` | Optional code |

### 2.4 Unit (`app/Unit.php`)

**Table:** `units`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `actual_name` | Full name |
| `short_name` | Display abbreviation |
| `allow_decimal` | Boolean |

### 2.5 Brands (`app/Brands.php`)

**Table:** `brands`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `name` | Brand name |
| `description` | Optional |

---

## 3. Financial & Accounting

### 3.1 Account (`app/Account.php`)

**Table:** `accounts`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `name` | Account name |
| `account_type_id` | FK to account_types |
| `account_number` | Account number |
| `balance` | Current balance |
| `is_closed` | Boolean |

### 3.2 AccountTransaction (`app/AccountTransaction.php`)

**Table:** `account_transactions`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `account_id` | FK to accounts |
| `type` | `debit` / `credit` |
| `amount` | Transaction amount |
| `transaction_id` | FK to transactions |
| `transaction_payment_id` | FK to transaction_payments |

### 3.3 TaxRate (`app/TaxRate.php`)

**Table:** `tax_rates`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `name` | Tax name |
| `amount` | Tax percentage |
| `is_tax_group` | Boolean — group of taxes |
| `for_group_tax` | Boolean |

---

## 4. Business Setup

### 4.1 BusinessLocation (`app/BusinessLocation.php`)

**Table:** `business_locations`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `name` | Location name |
| `location_id` | Custom short ID |
| `landmark`, `city`, `state`, `country` | Address |
| `mobile` | Phone |
| `invoice_scheme_id` | FK — invoice numbering |
| `invoice_layout_id` | FK — invoice template |
| `default_payment_accounts` | JSON |
| `is_active` | Boolean |

### 4.2 CashRegister (`app/CashRegister.php`)

**Table:** `cash_registers`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `user_id` | FK to users |
| `location_id` | FK to business_locations |
| `status` | `open` / `close` |
| `closing_amount` | Closing balance |
| `total_sale` | Sales total for session |

### 4.3 InvoiceLayout (`app/InvoiceLayout.php`)

**Table:** `invoice_layouts`

Configures how invoices are printed — logo, columns, footer, etc.

### 4.4 InvoiceScheme (`app/InvoiceScheme.php`)

**Table:** `invoice_schemes`

Configures invoice number format (prefix, suffix, auto-increment).

---

## 5. CRM / Contacts

### 5.1 CustomerGroup (`app/CustomerGroup.php`)

**Table:** `customer_groups`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `name` | Group name |
| `amount` | Discount % for group |
| `price_calculation_type` | How group price is calculated |

### 5.2 SellingPriceGroup (`app/SellingPriceGroup.php`)

**Table:** `selling_price_groups`

Different price levels (wholesale, retail, VIP, etc.)

### 5.3 Discount (`app/Discount.php`)

**Table:** `discounts`

Automatic discounts applying to sales.

---

## 6. Notifications & Media

### 6.1 NotificationTemplate (`app/NotificationTemplate.php`)

**Table:** `notification_templates`

| Key Fields | Description |
|---|---|
| `business_id` | Tenant FK |
| `type` | Notification type |
| `subject` | Email subject |
| `body` | Template body |
| `cc`, `bcc` | CC/BCC addresses |
| `whatsapp_text` | WhatsApp message |

### 6.2 Media (`app/Media.php`)

**Table:** `media` — Spatie media library table

Polymorphic media attachments to any model.

---

## 7. Misc

### 7.1 ReferenceCount (`app/ReferenceCount.php`)

**Table:** `reference_counts`

Auto-increment reference number counters per type per business.

### 7.2 System (`app/System.php`)

**Table:** `system`

Global system settings (not tenant-specific).

### 7.3 Warranty (`app/Warranty.php`)

**Table:** `warranties`

Product warranty definitions.

### 7.4 Barcode (`app/Barcode.php`)

**Table:** `barcodes`

Custom barcode label definitions.

### 7.5 DashboardConfiguration (`app/DashboardConfiguration.php`)

**Table:** `dashboard_configurations`

Per-user/per-business dashboard widget layout.

### 7.6 DocumentAndNote (`app/DocumentAndNote.php`)

**Table:** `document_and_notes`

Documents/notes attached to transactions or contacts.

---

## 8. Migration Conventions

### 8.1 Migration Naming

Files follow `YYYY_MM_DD_HHMMSS_description.php`.

### 8.2 Always Include `business_id`

All tenant tables must have:
```php
$table->unsignedInteger('business_id');
$table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
```

### 8.3 Decimal Fields

Price and quantity fields use `decimal(22, 4)` precision.

### 8.4 Soft Deletes

Most models do NOT use soft deletes — use `is_inactive` / `status` flags instead.

### 8.5 Settings and Form-Backed Tables

For settings pages or any form that persists to a table: every persisted form field must have a corresponding migration column. Use the Form Request `rules()` as the list of persisted keys (excluding file inputs, CSRF, and UI-only flags). Ensure the table schema matches before or in the same change set as the Blade/controller/Util. See AGENTS.md Section 4 (build order: migration first) and Section 9 checklist.

---

## 9. Query Patterns

### 9.1 Standard Tenant Query

```php
$business_id = request()->session()->get('user.business_id');

$contacts = Contact::where('business_id', $business_id)
    ->where('type', 'customer')
    ->where('contact_status', 'active')
    ->select(['id', 'name', 'mobile', 'balance'])
    ->orderBy('name')
    ->get();
```

### 9.2 Eager Loading (Avoid N+1)

```php
$transactions = Transaction::where('business_id', $business_id)
    ->with([
        'contact:id,name,mobile',
        'location:id,name',
        'payment_lines:id,transaction_id,amount,method',
    ])
    ->where('type', 'sell')
    ->where('status', 'final')
    ->get();
```

### 9.3 Stock Query

```php
$stock = VariationLocationDetails::where('location_id', $location_id)
    ->join('variations', 'variation_location_details.variation_id', '=', 'variations.id')
    ->join('products', 'variations.product_id', '=', 'products.id')
    ->where('products.business_id', $business_id)
    ->select(['products.name', 'variations.sub_sku', 'variation_location_details.qty_available'])
    ->get();
```

### 9.4 Large Dataset Processing

```php
Transaction::where('business_id', $business_id)
    ->chunk(200, function ($transactions) {
        foreach ($transactions as $transaction) {
            // process
        }
    });
```
