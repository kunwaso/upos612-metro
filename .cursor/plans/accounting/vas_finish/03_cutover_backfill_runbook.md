# VAS Accounting Cutover and Backfill Runbook

Last updated: 2026-03-28

## Purpose
Provide the operational sequence for opening-balance import, historical treasury backfill, parity review, and legacy-route retirement.

## Sequence
1. Bootstrap VAS chart, tax codes, sequences, periods, and settings for the pilot business.
2. Confirm posting map completeness and selected provider readiness.
3. Run opening-balance import for balances before the backfill start date.
4. Run historical treasury backfill for the agreed date range.
5. Generate parity report for treasury, AR, AP, inventory, and branch totals.
6. Investigate and resolve deltas until finance owner signs off.
7. Move cutover mode from `observe` to `redirect` for pilot branches.
8. Complete UAT personas and month-end close using VAS only.
9. Move cutover mode to `disabled`.
10. Remove active runtime references to legacy accounting.

## Command contract
- `php artisan vas:cutover:backfill {business_id} {--from=} {--to=} {--dry-run}`
- `php artisan vas:cutover:parity {business_id} {--period=} {--branch=*} {--format=screen|csv}`
- `php artisan vas:providers:health {business_id}`

## Dry-run expectations
- Backfill dry run must report opening-balance rows, historical transaction rows, treasury totals, and any skipped/unmappable rows.
- Parity report must show legacy vs VAS values and deltas for each signed-off section.

## Failure handling
- If backfill payload changes for a previously imported legacy source, the prior voucher is reversed and a new version is posted.
- If parity remains unresolved, cutover mode stays `observe`.
- If provider health is not ready for required integrations, provider-dependent go-live tasks remain blocked.

## Final retirement conditions
- No unresolved cutover blockers.
- All UAT personas marked complete.
- Month-end close completed in VAS only.
- Legacy-route mode switched to `disabled`.
- No active route or menu dependency on `account/*`.
