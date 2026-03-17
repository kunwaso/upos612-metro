# Site Manager — Welcome Page Override (New Site)

When you **copy the ProjectX module to a new Laravel site**, the new app does not include the root `WelcomeController` or the route change that allows ProjectX to serve the Metronic landing page at `/`. Follow these steps to enable the Site Manager welcome page on the new site.

## 1. Publish the WelcomeController

From the project root:

```bash
php artisan vendor:publish --tag=projectx-welcome
```

This copies `Modules/ProjectX/Resources/stubs/WelcomeController.php` to `app/Http/Controllers/WelcomeController.php`. The controller is generic: it calls `getModuleData('welcome_view')` and, if any module returns a view name, uses that view; otherwise it returns the default `view('welcome')`.

## 2. Update the home route

In `routes/web.php`, replace the closure that returns the welcome view with the controller action.

**Before:**

```php
Route::get('/', function () {
    return view('welcome');
});
```

**After:**

```php
Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index']);
```

Keep the same middleware (e.g. `setData`) around the group that contains this route.

## 3. Run migrations and permissions

On the new site, run ProjectX migrations so that `projectx_site_settings` exists:

```bash
php artisan module:migrate ProjectX
```

Then reset the permission cache so that `projectx.site_manager.edit` is available:

```bash
php artisan permission:cache-reset
```

## 4. Assign permission and use Site Manager

- Assign the **Edit Site / Welcome Page** permission (`projectx.site_manager.edit`) to the desired role(s).
- Open **ProjectX → Site Manager** in the sidebar to edit the welcome page content (hero title, subtitle, CTA, footer, nav items).
- Visit `/` to see the Metronic landing page driven by Site Manager settings.

## Summary

| Step | Action |
|------|--------|
| 1 | `php artisan vendor:publish --tag=projectx-welcome` |
| 2 | In `routes/web.php`, use `WelcomeController@index` for `GET /` |
| 3 | `php artisan module:migrate ProjectX` and `php artisan permission:cache-reset` |
| 4 | Assign `projectx.site_manager.edit` and use Site Manager in the UI |

No ProjectX-specific code is added to the root beyond the generic hook: the new site only needs the published controller and the single route change above.
