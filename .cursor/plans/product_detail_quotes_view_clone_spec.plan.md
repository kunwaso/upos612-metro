---
name: Product detail quotes view clone spec
overview: When cloning fabric_manager and quotes views to root product detail, copy all divs and input fields exactly; only replace routes, variable names (fabric → product), layout, and translation keys. No structural or markup changes. Implementation must support all quote capabilities listed below.
todos: []
isProject: false
---

# View clone specification (exact copy, minimal text replace)

This addendum applies to the **Product detail quotes clone to root** plan.

---

## Required quote capabilities (after implementation)

The cloned product-quote feature in root **must** support all of the following. When cloning controllers, models, views, and routes from ProjectX, ensure each capability is implemented and testable.


| Capability                           | What the user can do                                                                                  | Clone from ProjectX                                                                                                                                                  |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Make quote**                       | Create a new quote (from product detail or from sales/quotes).                                        | QuoteController::store, storeFromFabric → storeFromProduct; create view; detail_quotes form POST.                                                                    |
| **Single-item quote**                | Create a quote with one product line (e.g. from product detail Budget tab).                           | createSingleFabricQuote → createSingleProductQuote; StoreBudgetQuoteRequest; budget form.                                                                            |
| **Multi-item quote**                 | Create a quote with multiple product lines (add/remove lines, different products).                    | QuoteController::create (multi), store(StoreQuoteRequest); create view with lines; QuoteUtil::createMultiFabricQuote → createMultiProductQuote.                      |
| **Edit quote**                       | Change quote lines, customer, dates, amounts, etc. before sending.                                    | QuoteController::edit, update; edit view; UpdateQuoteRequest.                                                                                                        |
| **Send quote to client with email**  | Email the quote (and/or public link) to the customer.                                                 | QuoteController::send; SendQuoteRequest; QuoteMailer; mail_public_link view.                                                                                         |
| **Public link**                      | Share a link (e.g. /q/{token}) so the customer can view the quote without logging in.                 | Quote model public_token; PublicQuoteController::show; public_quote view; display/copy public URL in quote show.                                                     |
| **Password manager**                 | Set a password on the public link; customer must enter it to view. Remove password to make link open. | setPublicPassword (set/remove); PublicQuoteController::unlock; public_quote_password view; UnlockPublicQuoteRequest.                                                 |
| **Quote sign / approval**            | Customer views public quote and can confirm/sign (approve) the quote.                                 | PublicQuoteController::confirm; ConfirmPublicQuoteRequest; confirmation_signature, confirmed_at on Quote; public_quote view with confirm form/signature.             |
| **Remove signature**                 | Admin can clear the customer’s confirmation/signature so the quote can be edited or re-sent.          | QuoteController::clearSignature; ClearQuoteSignatureRequest; QuoteUtil::clearQuoteConfirmation; permission product_quote.admin_override + product_quote.edit.        |
| **Revert to draft**                  | If quote was converted to sale (transaction_id set), admin can revert quote back to draft.            | QuoteController::revertToDraft; RevertQuoteToDraftRequest; QuoteUtil::revertQuoteToDraft.                                                                            |
| **Create sales order**               | From a confirmed quote, create a sale (Transaction type=sell); quote links to transaction.            | QuoteController::sellPrefill; route sells.create?product_quote_id=; root SellPosController must accept product_quote_id; after-sale hook links quote.transaction_id. |
| **Sales orders list**                | List all sales that came from product quotes (DataTable: invoice, customer, total, view/edit).        | SalesController::index; orders-index view; join product_quotes on transaction_id.                                                                                    |
| **Sales order show**                 | View one sales order (transaction + quote info, link back to quote).                                  | SalesController::show; orders-show view.                                                                                                                             |
| **Sales order edit**                 | Edit the sale (lines, contact, location, payment, delivery date).                                     | SalesController::edit, update; SalesOrderEditUtil; orders-edit view; UpdateProjectxSalesOrderRequest → UpdateProductSalesOrderRequest.                               |
| **Sales order update status (hold)** | Mark order on hold / remove hold.                                                                     | SalesController::updateHoldStatus; UpdateProjectxOrderHoldStatusRequest.                                                                                             |
| **Sales order delete**               | Delete/cancel the sales order (optional: use core sell delete or dedicated route; unlink quote).      | If core has sell destroy, use it and unlink product_quotes.transaction_id; or add ProductSalesController::destroy.                                                   |


**Verification:** After implementation, a user can (1) create single- and multi-item quotes, (2) edit quotes, (3) send quote by email to client, (4) share public link and optionally set/remove password, (5) have client open link, enter password if set, view quote, and sign/approve, (6) admin can clear signature and (if applicable) revert to draft, (7) create sales order from quote (sells.create?product_quote_id=), (8) view/list sales orders from quotes, (9) view and edit sales order, (10) update hold status, (11) delete sales order where permitted.

---

## Rule: copy all divs, replace only text/routes/vars

For every view listed below:

1. **Copy the entire file** – all `<div>`, `<form>`, `<input>`, `<select>`, `<table>`, classes, and structure **exactly as in the source**.
2. **Replace only:**
  - **Routes:** `route('projectx.fabric_manager.*', ['fabric_id' => $fabric->id])` → `route('product.detail', ['id' => $product->id, 'tab' => '…'])` or `route('product.quotes.*')` as appropriate; `route('projectx.quotes.*')` → `route('product.quotes.*')`.
  - **Variables:** `$fabric` → `$product`, `fabric_id` → product id in URLs/forms where the backend expects product_id.
  - **Layout:** `@extends('projectx::layouts.main')` → `@extends('layouts.app')`; remove ProjectX-specific layout sections if any.
  - **Translation keys:** `__('projectx::lang.xxx')` → `__('product.xxx')` or `__('lang_v1.xxx')` (and add those keys to root `resources/lang/` so text stays the same).
  - **Asset paths:** `asset('modules/projectx/...')` → `asset('assets/...')` for any icons/media used in the cloned view.
3. **Do not:** remove divs, merge sections, change form field names/ids (except where backend requires a different name), or simplify the markup. The goal is **pixel/structure parity** with the source view.

---

## Source views to clone (exact divs + inputs)

### 1. Product detail tabs (from fabric_manager)


| Source (ProjectX)                                                                                                                                              | Root target                                                     | Replace                                                                                                                                                                         |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [dreampos/ProjectX/Resources/views/fabric_manager/budget.blade.php](dreampos/ProjectX/Resources/views/fabric_manager/budget.blade.php)                         | `resources/views/product/partials/detail_quotes.blade.php`      | All divs/cards/form identical. Form action → `product.quotes.store` with product id. Route names projectx.* → product.quotes.*; $fabric → $product; projectx::lang → root lang. |
| [dreampos/ProjectX/Resources/views/fabric_manager/files.blade.php](dreampos/ProjectX/Resources/views/fabric_manager/files.blade.php)                           | `resources/views/product/partials/detail_files.blade.php`       | Same grid/cards/upload card. Links → product.detail.files.upload/download/delete; $fabric → $product.                                                                           |
| [dreampos/ProjectX/Resources/views/fabric_manager/users.blade.php](dreampos/ProjectX/Resources/views/fabric_manager/users.blade.php)                           | `resources/views/product/partials/detail_contacts.blade.php`    | Same card/table tabs. Only replace routes and vars (fabric → product); keep all columns and layout.                                                                             |
| [dreampos/ProjectX/Resources/views/fabric_manager/activity.blade.php](dreampos/ProjectX/Resources/views/fabric_manager/activity.blade.php)                     | `resources/views/product/partials/detail_activity.blade.php`    | Same card, same tab list (Today/Week/Month/Year). Include the same timeline structure.                                                                                          |
| [dreampos/ProjectX/Resources/views/fabric_manager/_activity_timeline.blade.php](dreampos/ProjectX/Resources/views/fabric_manager/_activity_timeline.blade.php) | `resources/views/product/partials/_activity_timeline.blade.php` | Same timeline items; delete URL → product.detail.activity.delete; $fabric → $product.                                                                                           |


### 2. Quote views (from ProjectX quotes + sales)


| Source (ProjectX)                                                                                                                                    | Root target                                                                                | Replace                                                                                                                                                                             |
| ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [dreampos/ProjectX/Resources/views/sales/quote-show.blade.php](dreampos/ProjectX/Resources/views/sales/quote-show.blade.php)                         | `resources/views/product/quotes/show.blade.php` or `resources/views/quotes/show.blade.php` | All divs/sections identical. `projectx.quotes.`* → `product.quotes.`*; `projectx.sales` → product.quotes.index or product.detail; `projectx_quote_id` → product_quote_id if needed. |
| ProjectX quote edit view (if exists)                                                                                                                 | Root quote edit view                                                                       | Same: copy all divs/inputs; replace routes and lang.                                                                                                                                |
| [dreampos/ProjectX/Resources/views/quotes/public_quote.blade.php](dreampos/ProjectX/Resources/views/quotes/public_quote.blade.php)                   | `resources/views/quotes/public_quote.blade.php`                                            | Copy entire content; routes → product.quotes.public, product.quotes.public.confirm; layout → layouts.app or a minimal public layout.                                                |
| [dreampos/ProjectX/Resources/views/quotes/public_quote_password.blade.php](dreampos/ProjectX/Resources/views/quotes/public_quote_password.blade.php) | `resources/views/quotes/public_quote_password.blade.php`                                   | Same; unlock form action → product.quotes.public.unlock.                                                                                                                            |
| [dreampos/ProjectX/Resources/views/quotes/mail_public_link.blade.php](dreampos/ProjectX/Resources/views/quotes/mail_public_link.blade.php)           | `resources/views/emails/quote_public_link.blade.php`                                       | Copy; update any route in mail to product.quotes.public.                                                                                                                            |
| [dreampos/ProjectX/Resources/views/quotes/mail_confirmed.blade.php](dreampos/ProjectX/Resources/views/quotes/mail_confirmed.blade.php)               | `resources/views/emails/quote_confirmed.blade.php`                                         | Copy; replace any projectx text.                                                                                                                                                    |


---

## Checklist when cloning each file

- Every `<div>`, `<form>`, `<input>`, `<select>`, `<table>`, `<ul>`, `<li>` from the source is present in the root file.
- Only the following were changed: (1) route names, (2) $fabric / fabric_id → $product / product id, (3) @extends and asset paths, (4) __('projectx::lang.x') → __('product.x') or equivalent with same display text.
- Form `action`, `method`, and input `name` attributes are unchanged unless the root backend expects a different parameter (e.g. product_id instead of fabric_id in the URL).
- No divs or sections removed or merged for “simplification”.

This keeps the product detail Quotes, Activity, Files, and Contacts tabs visually and structurally the same as the fabric_manager and quotes views, so behaviour and UX stay consistent after removing the ProjectX folder.

**Final check:** Before considering the clone done, confirm every item in **Required quote capabilities** above works: make quote (single + multi), edit, send by email, public link, set/remove password, customer sign/approve, admin remove signature, revert to draft, create sales order, sales orders list/show/edit/update hold/delete.

---

## ProjectX → Root clone map (algorithm, controller, Util, routes)

Clone the following so all ProjectX quote and sales-order logic lives correctly in root for product detail. Replace `projectx_`* tables with root `product_quotes` / `product_quote_lines`; replace `fabric_id` with `product_id` where applicable.

### Controllers to clone


| ProjectX source                                            | Root target                                                                        | Notes                                                                                                                                                                                                                                                                           |
| ---------------------------------------------------------- | ---------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `QuoteController`                                          | `app/Http/Controllers/ProductQuoteController.php`                                  | index, create, store, storeFromFabric→storeFromProduct, show, edit, update, destroy, revertToDraft, clearSignature, setPublicPassword, sellPrefill, send, releaseInvoice. Permissions projectx.quote.* → product_quote.*. Routes projectx.quotes.* → product.quotes.*.          |
| `PublicQuoteController`                                    | `app/Http/Controllers/PublicQuoteController.php`                                   | show, unlock, confirm. Table product_quotes; routes product.quotes.public, product.quotes.public.unlock, product.quotes.public.confirm.                                                                                                                                         |
| `SalesController`                                          | `app/Http/Controllers/ProductSalesOrderController.php` (or `SalesOrderController`) | index (join product_quotes), show, edit, update, productSearch, updateHoldStatus. Permissions projectx.sales_order.* → product_sales_order.edit, product_sales_order.update_status. Routes projectx.sales.orders.* → product.sales.orders.*. Uses root Transaction (type=sell). |
| FabricManagerController (budget/activity/files/users only) | Data in `ProductController::detail` or `ProductDetailTabsController`               | Only the **data loading** for budget, activity, files, users tabs; no separate controller if product detail uses one detail() with tab param.                                                                                                                                   |


### Utils to clone


| ProjectX source                 | Root target                                                          | Notes                                                                                                                                                                                                                                                                                                                                                                 |
| ------------------------------- | -------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `QuoteUtil`                     | `app/Utils/QuoteUtil.php`                                            | createSingleFabricQuote→createSingleProductQuote, createMultiFabricQuote→createMultiProductQuote, getQuoteByIdForBusiness, linkQuoteToTransaction, revertQuoteToDraft, clearQuoteConfirmation, updatePublicLinkPassword, getConfirmedQuoteForSellPrefill, normalizeQuoteLinesForView, etc. Table product_quotes, product_quote_lines; Product instead of Fabric/Trim. |
| `FabricCostingUtil`             | `app/Utils/ProductCostingUtil.php`                                   | buildLinePayload(Product, input), getDropdownOptions, getDefaultCurrencyCode. No fabric/trim.                                                                                                                                                                                                                                                                         |
| `QuoteInvoiceReleaseService`    | `app/Utils/QuoteInvoiceReleaseService.php`                           | buildSellLinePayload (from product_quote_lines + Product/Variation), releaseToDraftInvoice if used. Product/Variation instead of Fabric/Trim.                                                                                                                                                                                                                         |
| `SalesOrderEditUtil`            | `app/Utils/SalesOrderEditUtil.php`                                   | getProjectxSellTransactionForEdit → getProductQuoteSellTransactionForEdit (join product_quotes); buildEditViewData, mapSellLinesForEdit, persistDeliveryDate, searchSellableVariations, update transaction via TransactionUtil. Table product_quotes.                                                                                                                 |
| `FabricActivityLogUtil`         | `app/Utils/ProductActivityLogUtil.php`                               | getForFabric→getForProduct; table product_activity_log.                                                                                                                                                                                                                                                                                                               |
| `ProjectXQuoteDisplayPresenter` | `app/Utils/QuoteDisplayPresenter.php` (or keep name)                 | presentQuote, presentLatestQuoteSummary, presentPublicQuote for product_quotes.                                                                                                                                                                                                                                                                                       |
| `ProjectXNumberFormatUtil`      | Use root `Util::num_f` / session or `app/Utils/NumberFormatUtil.php` | Currency/quantity precision for quote forms.                                                                                                                                                                                                                                                                                                                          |


### Form requests to clone


| ProjectX source                      | Root target                           |
| ------------------------------------ | ------------------------------------- |
| StoreBudgetQuoteRequest              | StoreProductQuoteRequest (product_id) |
| StoreQuoteRequest                    | StoreProductQuoteRequest (multi-line) |
| UpdateQuoteRequest                   | UpdateProductQuoteRequest             |
| SendQuoteRequest                     | SendQuoteRequest                      |
| SetPublicQuotePasswordRequest        | SetPublicQuotePasswordRequest         |
| ClearQuoteSignatureRequest           | ClearQuoteSignatureRequest            |
| RevertQuoteToDraftRequest            | RevertQuoteToDraftRequest             |
| ReleaseQuoteInvoiceRequest           | Optional (deprecated in ProjectX)     |
| UpdateProjectxSalesOrderRequest      | UpdateProductSalesOrderRequest        |
| UpdateProjectxOrderHoldStatusRequest | UpdateProductOrderHoldStatusRequest   |


### Root integration: create sale from quote

- **Sell form:** When creating a sale from a quote, root must pass `product_quote_id` (not `projectx_quote_id`) in the request (e.g. `route('sells.create', ['product_quote_id' => $quote->id])`).
- **After sale saved:** ProjectX uses `DataController::after_sale_saved` called by root `TransactionUtil` / `SellPosController` via `getModuleData('after_sale_saved', ...)`. After clone, root must **either** (a) call a root listener/service that links `product_quotes.transaction_id` when `product_quote_id` is in the request, or (b) add the same logic inline in the place that currently calls `getModuleData('after_sale_saved')` so that when `product_quote_id` is present, `QuoteUtil::linkQuoteToTransaction($business_id, $quote_id, $transaction->id)` runs. Do not depend on ProjectX module after clone.

### Routes to clone (routes/web.php)

- **Quotes:** product.quotes.index (sales list), product.quotes.create, product.quotes.store, product.quotes.show, product.quotes.edit, product.quotes.update, product.quotes.destroy, product.quotes.revert_draft, product.quotes.clear_signature, product.quotes.send, product.quotes.set_public_password, product.quotes.sell_prefill, product.quotes.release_invoice.
- **Product detail tabs:** product.detail (tab=quotes|activity|files|contacts), product.quotes.store from product (POST with product id), product.detail.files.upload, product.detail.files.download, product.detail.files.delete, product.detail.activity.delete.
- **Public:** product.quotes.public (GET /q/{publicToken}), product.quotes.public.unlock (POST), product.quotes.public.confirm (POST).
- **Sales orders:** product.sales.orders.index, product.sales.orders.show, product.sales.orders.edit, product.sales.orders.update, product.sales.orders.product_search, product.sales.orders.hold.update.

### Sales order views to clone (exact divs)


| ProjectX source                     | Root target                                                             |
| ----------------------------------- | ----------------------------------------------------------------------- |
| sales/index.blade.php (quotes list) | resources/views/product/quotes/index.blade.php or sales/index.blade.php |
| sales/quote-show.blade.php          | resources/views/product/quotes/show.blade.php                           |
| sales/quote-create.blade.php        | resources/views/product/quotes/create.blade.php                         |
| sales/quote-edit.blade.php          | resources/views/product/quotes/edit.blade.php                           |
| sales/orders-index.blade.php        | resources/views/product/sales/orders-index.blade.php                    |
| sales/orders-show.blade.php         | resources/views/product/sales/orders-show.blade.php                     |
| sales/orders-edit.blade.php         | resources/views/product/sales/orders-edit.blade.php                     |


Replace all `projectx.sales`, `projectx.quotes.`*, `projectx.sales.orders.`* with product.quotes.* and product.sales.orders.*. Replace `projectx_quote_id` with `product_quote_id` for the "Create sale from quote" link to sells.create.