# ProjectX Module

Products & Sales data management module with Metronic UI for the UPOS system.

**Version:** 1.0  
**Requires:** UPOS 6.11+ (Laravel 9, PHP ^8.0)

---

## Features

- Dashboard with sales/product KPI cards (today, this month, totals)
- Products listing with DataTables (AJAX-driven, server-side)
- Sales listing with DataTables + detail view
- Sidebar activity feed
- Metronic 8.3.3 admin UI (Bootstrap 5 + Tailwind CSS hybrid)
- Full install/uninstall flow via web route

---

## Directory Structure

```
Modules/ProjectX/
├── Config/config.php              # Module configuration (name, version)
├── Database/Migrations/           # Version tracking migration
├── Http/Controllers/
│   ├── DashboardController.php    # Dashboard + sidebar activity
│   ├── DataController.php         # Admin sidebar menu registration
│   ├── InstallController.php      # Install / uninstall logic
│   ├── ProductController.php      # Products listing
│   └── SalesController.php        # Sales listing + detail
├── Providers/
│   ├── ProjectXServiceProvider.php # Registers views, config, assets, translations
│   └── RouteServiceProvider.php    # Maps web + API routes
├── Resources/
│   ├── assets/                    # Metronic CSS, JS, plugins, media (published to public/)
│   ├── lang/en/lang.php           # English translations (67 keys)
│   ├── index.html                 # Metronic reference template (do not deploy)
│   └── views/
│       ├── layouts/
│       │   ├── main.blade.php     # Full Metronic layout
│       │   └── partial/           # aside, header, footer partials
│       ├── dashboard/index.blade.php
│       ├── products/index.blade.php
│       └── sales/
│           ├── index.blade.php
│           └── show.blade.php
├── Routes/
│   ├── web.php                    # 6 web routes (auth + middleware protected)
│   └── api.php                    # API routes (empty placeholder)
├── Utils/ProjectXUtil.php         # Business logic for dashboard data
├── module.json                    # nwidart module manifest
└── readme.md                      # This file
```

---

## Installation (Fresh Setup)

### Step 1 — Copy the module folder

Place the entire `ProjectX/` folder into:

```
Modules/ProjectX/
```

### Step 2 — Enable the module

Open `modules_statuses.json` in the project root and add:

```json
"ProjectX": true
```

Or run:

```bash
php artisan module:enable ProjectX
```

### Step 3 — Publish assets to the public directory

This is **required** — the UI will not load without it:

```bash
php artisan vendor:publish --tag=projectx-assets --force
```

This copies `Modules/ProjectX/Resources/assets/` → `public/modules/projectx/`.

### Step 4 — Run the module migration

```bash
php artisan module:migrate ProjectX
```

This registers the module version (`projectx_version = 1.0`) in the `system` table.

### Step 5 — Clear all caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### Step 6 — Trigger the install route (first-time only)

Log in as a **superadmin** user and visit:

```
https://your-domain.com/projectx/install
```

This runs the migration via the web UI and publishes assets automatically.  
After a successful install, the route auto-redirects to the home page.

> **Note:** If you already completed Steps 3 and 4 manually, the install route will detect the existing version and return 404 (already installed). That's expected — skip this step.

### Step 7 — Verify

Visit `https://your-domain.com/projectx` — you should see the Metronic dashboard.

---

## Uninstallation

Log in as a **superadmin** and visit:

```
https://your-domain.com/projectx/install/uninstall
```

This removes:
- The `projectx_version` row from the `system` table
- The `public/modules/projectx/` asset directory

To fully disable:

```bash
php artisan module:disable ProjectX
```

---

## Routes

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/projectx` | `projectx.index` | DashboardController@index |
| GET | `/projectx/sidebar-activity` | `projectx.sidebar_activity` | DashboardController@getSidebarActivity |
| GET | `/projectx/fabric-list` | `projectx.products` | ProductController@index |
| GET | `/projectx/sales` | `projectx.sales` | SalesController@index |
| GET | `/projectx/sales/{id}` | `projectx.sales.show` | SalesController@show |
| GET | `/projectx/install` | — | InstallController@index |
| POST | `/projectx/install` | — | InstallController@install |
| GET | `/projectx/install/uninstall` | — | InstallController@uninstall |

All routes are behind `auth`, `SetSessionData`, `language`, `timezone`, and `AdminSidebarMenu` middleware.

---

## Permissions Required

| Action | Permission |
|--------|------------|
| View products | `product.view` |
| View sales | `sell.view` or `direct_sell.view` |
| Install / uninstall | `superadmin` |

The sidebar menu only appears if the user holds at least one of the above view permissions.

---

## Asset Pipeline

Assets live in `Modules/ProjectX/Resources/assets/` and must be published to `public/modules/projectx/`.

**Key files:**

| Asset | Path (after publish) |
|-------|---------------------|
| Metronic CSS | `public/modules/projectx/css/style.bundle.css` |
| Global plugins CSS | `public/modules/projectx/plugins/global/plugins.bundle.css` |
| Global plugins JS | `public/modules/projectx/plugins/global/plugins.bundle.js` |
| Metronic scripts | `public/modules/projectx/js/scripts.bundle.js` |
| DataTables | `public/modules/projectx/plugins/custom/datatables/datatables.bundle.{css,js}` |
| FullCalendar | `public/modules/projectx/plugins/custom/fullcalendar/fullcalendar.bundle.{css,js}` |

**amCharts** is loaded from CDN (`https://cdn.amcharts.com/lib/5/`), not from local files.

If you update any file under `Resources/assets/`, re-publish:

```bash
php artisan vendor:publish --tag=projectx-assets --force
```

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Page loads but no styles / broken layout | Assets not published | `php artisan vendor:publish --tag=projectx-assets --force` |
| 404 on `/projectx` | Module not enabled | Add `"ProjectX": true` to `modules_statuses.json` |
| Sidebar menu missing | Module not installed via DB | Visit `/projectx/install` as superadmin |
| Stale views after editing blades | Cached compiled views | `php artisan view:clear` |
| Config changes not reflected | Cached config | `php artisan config:clear` |
| Charts not rendering | amCharts CDN blocked | Check network/firewall for `cdn.amcharts.com` |
| `cache:clear` fails | Missing storage directory | Create `storage/framework/cache/data/` manually |

---

## Development Notes

- **Models** are in `app/` root (e.g. `App\Product`, `App\Transaction`) — not `app/Models/`
- **Business logic** goes in `Utils/ProjectXUtil.php` — keep controllers thin
- **Multi-tenant**: every query must include `->where('business_id', $business_id)`
- **Translations**: use `__('projectx::lang.key')` — never hardcode UI strings
- **UI reference**: `Resources/index.html` is the original Metronic template; keep it for reference but do not deploy it
- The `main.blade copy.php` file is a backup — can be removed in production
