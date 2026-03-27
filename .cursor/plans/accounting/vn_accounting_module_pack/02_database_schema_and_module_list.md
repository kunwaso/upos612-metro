# Database Schema and Module List

## Module list

### Core platform
1. Authentication and users
2. Roles and permissions
3. Approval workflow
4. Audit logs
5. Attachments
6. Background jobs and notifications

### Accounting domain
7. Chart of accounts
8. Journal and ledger
9. Cash and bank
10. Accounts receivable
11. Accounts payable
12. Sales accounting
13. Purchase accounting
14. Tax and invoice
15. Inventory
16. Fixed assets
17. Closing and adjustments
18. Reporting and financial statements
19. Integration hub
20. Import/export tools

## Core schema

### organizations
- id
- code
- name
- tax_code
- address
- phone
- email
- legal_representative
- base_currency
- fiscal_year_start_month
- timezone
- status
- created_at
- updated_at

### branches
- id
- organization_id
- code
- name
- address
- tax_code
- is_head_office
- status
- created_at
- updated_at

### accounting_periods
- id
- organization_id
- year
- month
- start_date
- end_date
- status (open, soft_locked, closed)
- closed_by
- closed_at
- notes

### users
- id
- name
- email
- password_hash
- status
- last_login_at
- created_at
- updated_at

### roles
- id
- code
- name
- description

### permissions
- id
- code
- name
- module
- action

### user_roles
- user_id
- role_id
- organization_id
- branch_id nullable

### approval_workflows
- id
- code
- name
- document_type
- organization_id
- is_active

### approval_steps
- id
- workflow_id
- step_no
- approver_role_id
- approver_user_id nullable
- action_type
- is_required

### audit_logs
- id
- organization_id
- user_id
- entity_type
- entity_id
- action
- old_values json
- new_values json
- ip_address
- user_agent
- created_at

### accounts
- id
- organization_id
- code
- name_vi
- name_en nullable
- parent_id nullable
- account_type
- normal_balance
- level
- is_postable
- requires_customer
- requires_vendor
- requires_employee
- requires_department
- requires_cost_center
- requires_project
- requires_item
- requires_asset
- fs_mapping_code
- status
- effective_from
- effective_to nullable

### vouchers
- id
- organization_id
- branch_id
- period_id
- voucher_type
- voucher_no
- voucher_date
- posting_date
- document_date nullable
- document_no nullable
- description
- currency_code
- exchange_rate
- total_amount
- status
- source_module
- source_id nullable
- workflow_status nullable
- created_by
- approved_by nullable
- posted_by nullable
- posted_at nullable
- reversed_voucher_id nullable
- created_at
- updated_at

### voucher_lines
- id
- voucher_id
- line_no
- description
- debit_account_id nullable
- credit_account_id nullable
- amount
- amount_fc nullable
- currency_code nullable
- customer_id nullable
- vendor_id nullable
- employee_id nullable
- department_id nullable
- cost_center_id nullable
- project_id nullable
- item_id nullable
- warehouse_id nullable
- asset_id nullable
- tax_code_id nullable
- due_date nullable
- reference_type nullable
- reference_id nullable

### journal_entries
- id
- organization_id
- voucher_id
- entry_no
- entry_date
- posting_date
- account_id
- debit_amount
- credit_amount
- debit_amount_fc nullable
- credit_amount_fc nullable
- currency_code nullable
- exchange_rate nullable
- customer_id nullable
- vendor_id nullable
- employee_id nullable
- department_id nullable
- cost_center_id nullable
- project_id nullable
- item_id nullable
- warehouse_id nullable
- asset_id nullable
- tax_code_id nullable
- description
- created_at

### ledger_balances
- id
- organization_id
- period_id
- account_id
- customer_id nullable
- vendor_id nullable
- employee_id nullable
- department_id nullable
- cost_center_id nullable
- project_id nullable
- item_id nullable
- warehouse_id nullable
- asset_id nullable
- opening_debit
- opening_credit
- movement_debit
- movement_credit
- closing_debit
- closing_credit

### Supporting transaction domains
Also include:
- customers
- vendors
- employees
- departments
- cost_centers
- projects
- currencies
- exchange_rates
- tax_codes
- payment_terms
- cash_accounts
- bank_accounts
- bank_statements
- bank_statement_lines
- sales_invoices
- sales_invoice_lines
- customer_receipts
- purchase_invoices
- purchase_invoice_lines
- vendor_payments
- items
- warehouses
- inventory_transactions
- inventory_transaction_lines
- inventory_balances
- fixed_assets
- asset_depreciation_runs
- asset_depreciation_lines
- tax_declarations
- e_invoice_providers
- e_invoice_logs
- report_definitions
- report_runs
- attachments
- notes

## Suggested technical rules
- Posted vouchers must be immutable
- Journal entries generated from vouchers should never be edited directly
- Reversal creates a new voucher with opposite entries
- Each posted voucher must balance: total debit = total credit
- Period close prevents posting into closed periods unless reopened by authorized role
- Report mappings should be versioned by regime and effective date
