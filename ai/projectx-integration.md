# ProjectX Integration with Root Core

This document is the stable reference for how `Modules/ProjectX` should extend root/core behavior without hard-coding ProjectX logic into root controllers or views.

Use this instead of referencing ad hoc plan files under `.cursor/plans/`.

---

## 1. Core Rule

When ProjectX needs to change root behavior:

- use **hooks** via `ModuleUtil::getModuleData()`
- use **view composers** for root Blade views
- keep root views safe with null-coalesced variables
- avoid adding ProjectX-specific workflow code directly to root controllers

The goal is for a compatible root base to support ProjectX without patching core controllers every time the module moves between installs.

---

## 2. Hooks Pattern

Implement hook methods in `Modules\ProjectX\Http\Controllers\DataController` and let the root call them through `ModuleUtil::getModuleData()`.

Common hook examples already used or expected in this repo:

- `after_product_saved`
- `before_product_deleted`
- `after_sale_saved`
- `user_permissions`
- `modifyAdminMenu`

Guidelines:

- Hook arguments should be plain arrays with the minimum data needed by the module.
- Root code should call the generic hook, not a ProjectX class directly.
- Module hook methods should no-op safely when required data is missing.
- Hook failures should be logged carefully and should not corrupt the root transaction flow.

---

## 3. View Composer Pattern

Use view composers in `Modules/ProjectX/Providers/ProjectXServiceProvider.php` when ProjectX must inject data into root Blade views.

Typical uses:

- `product.create`
- `product.edit`
- other root views that need ProjectX flags, link state, or prepared config

Guidelines:

- Root Blade should rely on null-coalesced variables such as `$projectx_enabled ?? false`.
- Prepare all ProjectX-specific display/config data in the composer or module-side utility logic.
- Do not add large ProjectX `@php` blocks to root or module Blade files.

---

## 4. Root Compatibility Checklist

When a root base is expected to support ProjectX, verify these extension points still exist and remain generic:

- Product save/update/delete flows call module hooks instead of embedding ProjectX logic inline.
- Root product views tolerate absent ProjectX variables by using null coalescing.
- Quote-to-sale linking flows pass enough request data for an `after_sale_saved` hook to complete module-side linking.
- Header or dashboard links to ProjectX are either hook/composer driven or isolated behind module checks.
- Root language keys required by shared product UI are still present.

If one of these points is missing, fix the root extension point generically rather than adding more ProjectX-specific code in the root.

---

## 5. What Not To Do

- Do not add ProjectX-specific persistence logic to `ProductController`, `SellPosController`, or similar root controllers when a hook can handle it.
- Do not hard-code ProjectX-only UI decisions into root Blade beyond optional extension points and null-coalesced variables.
- Do not move ProjectX business workflows into a second root-side service layer.
- Do not treat `.cursor/plans/*.plan.md` as source-of-truth architecture docs.

---

## 6. When To Read This

Read this document when working on:

- ProjectX product integration
- ProjectX quote or sales integration with root flows
- ProjectX-driven root menu/header/dashboard links
- any module feature that extends root behavior through hooks or view composers
