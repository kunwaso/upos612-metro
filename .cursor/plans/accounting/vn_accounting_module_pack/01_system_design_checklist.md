# System Design Checklist

## 1. Scope and compliance
- Define target users: SME, enterprise, FDI, multi-branch, service, trading, manufacturing
- Define legal/accounting scope:
  - VAS-oriented reporting
  - VAT, CIT, PIT relevant workflows
  - e-invoice integration scope
  - digital signature scope
- Decide supported entities:
  - single company
  - multiple legal entities
  - branch/accounting dependent units
- Define accounting periods:
  - month
  - quarter
  - year
  - lock/reopen permissions

## 2. Architecture
- Modular monolith or service-oriented architecture
- API-first boundaries between modules
- Event/audit architecture for posting and reversals
- Background job support for reports, imports, reconciliations
- Attachment storage strategy
- Search/indexing strategy for vouchers, invoices, documents

## 3. Core accounting engine
- Double-entry journal model
- Voucher-to-journal posting pipeline
- Draft -> approved -> posted -> reversed lifecycle
- Immutable posted entries
- Reversal and adjustment flow
- Period close and year-end close
- Auto-numbering and numbering by document type/branch/period
- Ledger by account and subledger dimensions

## 4. Master data
- Chart of accounts
- Customers
- Vendors
- Employees
- Departments
- Cost centers
- Projects/contracts
- Warehouses
- Items and services
- Banks and cash accounts
- Tax codes and tax rates
- Currencies and exchange rates
- Fixed asset categories
- Payment terms

## 5. Functional modules
- General ledger
- Cash and bank
- Accounts receivable
- Accounts payable
- Sales
- Purchasing
- Inventory
- Fixed assets
- Tax
- Reporting
- Closing
- User/role/approval management
- Import/export/integration

## 6. Controls and security
- Role-based access control
- Segregation of duties
- Approval workflows
- Audit trail for every change
- Change reason required for reversal/reopen actions
- Sensitive action logs
- Document attachment retention
- Backup and restore
- Data encryption at rest and in transit

## 7. Performance and operability
- Indexing strategy for ledger/report queries
- Report snapshot/caching strategy
- Queue workers for heavy jobs
- Monitoring and alerting
- Import validation and error reporting
- End-of-month performance test scenarios

## 8. Reporting and outputs
- Trial balance
- General ledger
- Subsidiary ledgers
- AR aging
- AP aging
- Cash/bank books
- Inventory movement and valuation
- Fixed asset register and depreciation
- VAT purchase/sales summaries
- Financial statements
- Export to Excel/PDF/XML as needed

## 9. Integration readiness
- e-invoice provider adapter
- bank statement import/reconciliation
- payroll import
- tax declaration/export support
- document OCR capture later phase
- API/webhook design for ERP/CRM/ecommerce

## 10. Quality gates
- Posting engine unit tests
- Period close tests
- Reversal tests
- Multi-currency tests
- Tax calculation tests
- Inventory valuation tests
- Financial statement balancing tests
- UAT scenarios by accountant persona
