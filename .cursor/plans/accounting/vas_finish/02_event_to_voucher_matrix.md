# VAS Accounting Finish Event-to-Voucher Matrix

Last updated: 2026-03-28

## Core accounting events

| Event | Source type | Voucher type / sequence | Module area | Posting rule |
| --- | --- | --- | --- | --- |
| Manual journal | `manual` | `general_journal` | `accounting` | User-entered balanced lines |
| Sales invoice | `sell` | `sales_invoice` | `invoices` | Dr AR / Cr revenue / Cr VAT |
| Sales return | `sell_return` | `sales_credit_note` | `receivables` | Reverse revenue/VAT and restore inventory/COGS where applicable |
| Purchase invoice | `purchase` | `purchase_invoice` | `payables` | Dr inventory or expense / Dr VAT input / Cr AP |
| Purchase return | `purchase_return` | `purchase_debit_note` | `payables` | Reverse inventory/AP components |
| Expense | `expense` | `purchase_invoice` | `payables` | Dr expense / Dr VAT input / Cr AP or cash/bank |
| Customer receipt | `transaction_payment` | `cash_receipt` or `bank_receipt` | `cash_bank` | Dr cash/bank / Cr AR |
| Vendor payment | `transaction_payment` | `cash_payment` or `bank_payment` | `cash_bank` | Dr AP / Cr cash/bank |
| Payroll accrual | `payroll_batch` | `payroll_accrual` | `payroll` | Dr salary expense / Cr payroll payable |
| Payroll payment | `transaction_payment` | `payroll_payment` | `payroll` | Dr payroll payable / Cr cash/bank |

## Finish-plan historical import events

| Event | Source type | Voucher type / sequence | Module area | Posting rule |
| --- | --- | --- | --- | --- |
| Legacy opening balance | `legacy_opening_balance` | `opening_balance` | `accounting` | Dr or Cr treasury account vs migration clearing account |
| Legacy treasury transaction | `legacy_account_transaction` | `historical_treasury` | `cash_bank` | Treasury movement mirrored against migration clearing account |

## VAS-native warehouse document events

| Document type | Source type | Voucher type / sequence | Module area | Posting rule |
| --- | --- | --- | --- | --- |
| Warehouse receipt | `inventory_document` | `inventory_receipt` | `inventory` | Dr inventory / Cr offset account |
| Warehouse issue | `inventory_document` | `inventory_issue` | `inventory` | Dr offset account / Cr inventory |
| Warehouse transfer | `inventory_document` | `inventory_transfer` | `inventory` | Dr transfer clearing on destination warehouse / Cr inventory on source warehouse |
| Warehouse adjustment increase | `inventory_document` | `inventory_adjustment` | `inventory` | Dr inventory / Cr adjustment offset |
| Warehouse adjustment decrease | `inventory_document` | `inventory_adjustment` | `inventory` | Dr adjustment offset / Cr inventory |

## Close-center blockers tied to events
- Unposted warehouse documents block close.
- Unmatched bank statement lines block close.
- Unresolved posting failures block close.
- Draft, pending-approval, or approved-but-unposted vouchers block close.
- Pending depreciation rows block close.
