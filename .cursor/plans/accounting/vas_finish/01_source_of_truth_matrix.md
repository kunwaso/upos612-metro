# VAS Accounting Finish Source-of-Truth Matrix

Last updated: 2026-03-28

## Goal
Lock the authoritative runtime source for each finance surface during finish-plan execution so cutover, parity, and retirement work do not drift.

## Matrix

| Area | Operational source | Accounting source | Cutover target | Legacy dependency status |
| --- | --- | --- | --- | --- |
| Cash / bank master | `vas_cashbooks`, `vas_bank_accounts` | `vas_vouchers`, `vas_journal_entries` | VAS only | Legacy `accounts` / `account_transactions` remain parity input until retirement |
| Cash / bank reconciliation | `vas_bank_statement_imports`, `vas_bank_statement_lines` | `vas_vouchers`, `vas_journal_entries` | VAS only | No legacy reconciliation source |
| Sales / receipts | `transactions`, `transaction_payments` | `vas_vouchers`, `vas_journal_entries` | VAS only | Legacy treasury only for historical parity |
| Purchases / payments | `transactions`, `transaction_payments` | `vas_vouchers`, `vas_journal_entries` | VAS only | Legacy treasury only for historical parity |
| AR / AP | `transactions`, `transaction_payments`, allocation tables | `vas_vouchers`, `vas_voucher_lines`, allocation tables | VAS only | Legacy comparison comes from `TransactionUtil` due calculations |
| Tax / VAT | VAS voucher and tax-code data | `vas_journal_entries`, `vas_tax_codes` | VAS only | No legacy tax engine reuse |
| Inventory valuation | VAS warehouse docs plus existing stock data during transition | `vas_vouchers`, `vas_journal_entries`, warehouse docs | VAS only | Upstream stock remains comparison input during transition |
| Tools / fixed assets | VAS tables | `vas_vouchers`, `vas_journal_entries` | VAS only | No legacy dependency |
| Payroll bridge | Essentials payroll | `vas_vouchers`, `vas_journal_entries` | VAS only | No legacy dependency |
| Contracts / loans / budgets | VAS tables | `vas_vouchers`, `vas_journal_entries` | VAS only | No legacy dependency |
| Reports | VAS reporting services | VAS ledgers and enterprise tables | VAS only | Legacy reports are parity reference only until disabled |
| Cutover / rollout | VAS cutover settings and parity services | VAS only | VAS only | Legacy routes stay behind cutover mode until retirement |

## Legacy retirement rule
- `app/Http/Controllers/AccountController.php` and `app/Http/Controllers/AccountReportsController.php` are parity and rollback references only during finish-plan execution.
- No new feature work may add dependencies on `account/*`, `account_types`, or `account_transactions`.
- After parity sign-off and legacy-route disablement, runtime references to legacy accounting must be removed from active routes and menus.
