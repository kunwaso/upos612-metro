# ProjectX Contacts Module — Implementation Plan

## Summary

The Contacts feature in ProjectX uses **root contact data** (`App\Contact`, `App\Utils\ContactUtil`, `App\CustomerGroup`). All create/update/delete operations go through the root logic so data stays in sync with the main UPOS contact module. The UI follows the Metronic 8.3.3 style from `Modules/ProjectX/Resources/html/apps/contacts`.

---

## 1. Reference UI (HTML)

| Page | Reference file | Blade |
|------|----------------|-------|
| List / Getting started | `getting-started.html` | `contacts/index.blade.php` |
| Add contact | `add-contact.html` | `contacts/create.blade.php` |
| Edit contact | `edit-contact.html` | `contacts/edit.blade.php` |
| View contact | `view-contact.html` | `contacts/show.blade.php` |

**UI patterns used:** Toolbar (`toolbar d-flex flex-stack`, breadcrumb, Filter + Create), three-column layout (`row g-7`), Groups card (`card card-flush`), Search/list card (`form-control-solid ps-13`, scroll list with `symbol symbol-40px symbol-circle`), main content card, form rows (`fv-row mb-7`, `form-control form-control-solid`, `form-select form-select-solid`), view contact profile and tabs.

---

## 2. Files Created / Updated

### Backend

| File | Purpose |
|------|--------|
| `Modules/ProjectX/Http/Controllers/ContactController.php` | Index, create, store, show, edit, update, destroy. Uses `App\Contact`, `App\CustomerGroup`, `App\Utils\ContactUtil`, `App\Utils\ModuleUtil`, `App\Utils\Util`. All queries scoped by `business_id`. |
| `Modules/ProjectX/Routes/web.php` | Routes: `GET/POST /projectx/contacts`, `GET/PUT-PATCH/DELETE /projectx/contacts/{id}`, `GET .../create`, `GET .../edit`. |

### Views (Blade)

| File | Purpose |
|------|--------|
| `Modules/ProjectX/Resources/views/contacts/index.blade.php` | List: toolbar, groups card, list card, welcome card. |
| `Modules/ProjectX/Resources/views/contacts/create.blade.php` | Add contact: toolbar, groups card, form card with store action. |
| `Modules/ProjectX/Resources/views/contacts/edit.blade.php` | Edit contact: same layout, form with update action. |
| `Modules/ProjectX/Resources/views/contacts/show.blade.php` | View contact: toolbar, groups/list cards, contact details card (profile + General tab), delete modal. |
| `Modules/ProjectX/Resources/views/contacts/partials/_toolbar.blade.php` | Shared toolbar (title, breadcrumb, optional filter, primary button). |
| `Modules/ProjectX/Resources/views/contacts/partials/_groups_card.blade.php` | Groups sidebar: All / Customers / Suppliers links with counts, Customer groups link, Add contact button. |
| `Modules/ProjectX/Resources/views/contacts/partials/_list_card.blade.php` | Search input + scroll list of contacts (avatar initial, name link, email/mobile). |
| `Modules/ProjectX/Resources/views/contacts/partials/_form.blade.php` | Shared form fields: type, contact_id, customer_group_id, name, supplier_business_name, email, mobile, city, country, address_line_1, pay_term, opening_balance, credit_limit, notes (custom_field1). |

### Layout

| File | Change |
|------|--------|
| `Modules/ProjectX/Resources/views/layouts/partial/aside.blade.php` | Added “Contacts” tile linking to `projectx.contacts.index` (guarded by supplier/customer view permissions). |

---

## 3. Data Flow

- **Index:** `business_id` from session; filter by `type` (supplier/customer); optional `search`; load contacts (and counts for groups). Same permissions as root (`supplier.view`, `customer.view`, `view_own`).
- **Create:** Types and customer groups by permission; store via `ContactUtil::createNewContact($input)`; fire `ContactCreatedOrModified`; redirect to show.
- **Update:** Load contact by id + business_id; update via `ContactUtil::updateContact($input, $id, $business_id)`; fire event; redirect to show.
- **Delete:** Only if no transactions and not default; same checks as root; return JSON with redirect.
- **Single name field:** Form sends `name`; controller sets `first_name = name` when first/last name are empty, then builds `name` from name_array for root compatibility.

---

## 4. Permissions

Reuse root: `supplier.view`, `supplier.view_own`, `supplier.create`, `supplier.update`, `supplier.delete`, `customer.view`, `customer.view_own`, `customer.create`, `customer.update`, `customer.delete`. Aside menu shows Contacts only if user has at least one of the view/view_own permissions.

---

## 5. Customer Groups

No new tables or controllers. “Customer groups” link in the groups card goes to root `route('customer-group.index')`. ProjectX only displays and uses root customer groups (e.g. in form dropdown).

---

## 6. Optional Next Steps

- **Search:** Index already supports `?search=...`; list card form can POST/GET with search (client-side filter or server-side already in place).
- **DataTables:** Replace the scroll list with server-side DataTables using `ContactUtil::getContactQuery()` and the same filters as root for large datasets.
- **Import / Ledger:** Link to root `contacts.import` and contact ledger from the view page if needed.
- **Validation:** Add a FormRequest (e.g. `StoreContactRequest` / `UpdateContactRequest`) mirroring root validation rules.

---

## 7. Checklist

- [x] All contact queries use `business_id` from session.
- [x] Create/update use `ContactUtil::createNewContact` / `updateContact`.
- [x] No new Contact/CustomerGroup models; use `App\*`.
- [x] Blade structure and classes follow `Resources/html/apps/contacts` (toolbar, cards, form, list, view).
- [x] All views under `Modules/ProjectX/Resources/views/contacts/`.
- [x] Asset paths use `asset('modules/projectx/...')` (layout already does).
- [x] Contacts menu item in ProjectX aside with permission check.
