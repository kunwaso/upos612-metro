# Detailed Implementation Plan

## Phase 0 - Discovery and foundation
### Tasks
- Gather business requirements by persona
- Define MVP and non-MVP scope
- Write accounting transaction matrix
- Confirm reporting list
- Confirm approvals, branch model, and currency needs
- Produce workflows, ERD, and permission matrix

## Phase 1 - Platform and core ledger
### Tasks
- Set up project structure, CI, coding standards, migrations, queue, storage
- Implement users, roles, permissions, organizations, branches
- Implement approval workflow engine and audit logs
- Implement chart of accounts and master data
- Implement voucher lifecycle
- Implement posting service and journal generation
- Implement reversal flow
- Implement period validation and ledger balance rollup

## Phase 2 - Cash, bank, AR, AP
### Tasks
- Cash account and bank account masters
- Receipt/payment flows
- Bank statement import and reconciliation
- Sales invoice and customer receipt allocation
- Purchase invoice and vendor payment allocation
- Aging reports and statements

## Phase 3 - Tax and e-invoice
### Tasks
- Implement tax code usage rules
- Build VAT extraction logic and summaries
- Create e-invoice adapter interface
- Implement one provider first
- Log requests/responses and retries
- Add digital signature hooks

## Phase 4 - Inventory
### Tasks
- Items and warehouses
- Goods receipt, issue, transfer, adjustment
- Moving average valuation
- Inventory accounting entries
- Stock card and inventory reconciliation reports

## Phase 5 - Fixed assets
### Tasks
- Asset master
- Acquisition/capitalization
- Monthly depreciation run
- Depreciation vouchers
- Asset register and disposal flow

## Phase 6 - Closing and financial reporting
### Tasks
- Accruals and prepaids
- FX revaluation
- Month-end checklist
- Soft lock and hard close
- Year-end transfer
- Statement mapping engine
- Financial statements and notes

## Phase 7 - Integrations and operations
### Tasks
- CSV/Excel imports
- Opening balance import
- Payroll import
- API/webhook layer
- Notifications and scheduled reports
- Runbooks and monitoring

## Phase 8 - Hardening, UAT, rollout
### Tasks
- Full regression suite
- Security review
- Performance test
- Backup/restore test
- UAT with accountants
- Pilot rollout
- Production launch
