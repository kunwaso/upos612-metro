# Laravel Vietnam Accounting Scaffold

This starter pack includes:
- Laravel migrations for core accounting tables
- Seeders for roles, permissions, organization, periods, currencies, tax codes, and sample chart of accounts
- Module-style folder scaffold for Core, Accounting, Finance, Operations, and Integration
- Notes for next implementation steps

## Included domains
- Core: auth/roles/permissions/audit/attachments/approvals
- Accounting: chart of accounts, vouchers, journal entries, ledger balances, periods, reporting
- Finance: cash/bank, AR, AP, tax, e-invoice
- Operations: inventory, fixed assets
- Integration: imports/exports/payroll/banking/external APIs

## Suggested install
1. Copy `database/migrations/*` into your Laravel project
2. Copy `database/seeders/*` into your Laravel project
3. Copy the `Modules/*` folders into your Laravel app if you want a module-style structure
4. Register seeders in `DatabaseSeeder.php`
5. Run:

```bash
php artisan migrate
php artisan db:seed --class=AccountingCoreSeeder
php artisan db:seed --class=AccountingReferenceSeeder
php artisan db:seed --class=SampleChartOfAccountsSeeder
```

## Notes
- This is a starter scaffold, not a complete accounting product.
- Posted voucher immutability, posting services, approvals, reporting logic, and validation rules still need to be implemented in application code.
- Migration names use a single timestamp prefix for portability; adjust timestamps if merging into an existing app.
