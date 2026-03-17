# Browser Audit Workflow

Use this workflow when the user asks for `audit and fix: <url>` or `interactive web audit: <url>`.

## Prompt Contract

- `audit and fix: <url>`: run an interactive audit first, persist findings, inspect code, fix the issue, then verify with Playwright MCP and a rerun of `audit_web`.
- `interactive web audit: <url>`: run the interactive audit and return the findings plus next-step recommendation without making code changes unless the user also asked for a fix.

## Tool Order

1. Start the dedicated Chrome debug browser with `[scripts/open-audit-chrome.ps1](/d:/wamp64/www/upos612/scripts/open-audit-chrome.ps1)`.
2. Run `audit_web` in `mode=interactive` with `persist_report=true`.
3. Read `report.json` / `report.md` from `output/playwright/audit-web-mcp/reports/<session>/`.
4. If findings are empty, ambiguous, timing-sensitive, or performance-related, escalate to Chrome DevTools MCP.
5. Fix the issue in the repo.
6. Verify with Playwright MCP snapshot/screenshot and a rerun of `audit_web`.

## Persistence Rules

- Persist interactive runs under `output/playwright/audit-web-mcp/reports/<timestamp>-<slug>/`.
- Keep `report.json`, `report.md`, screenshot, trace, and optional `storage-state.json` together in the session directory.
- Update `latest.json` and `latest.md` after each persisted audit so agents can resume quickly.

## Escalate To Chrome DevTools MCP When

- `triageSummary.shouldEscalateToDevtools` is `true`.
- The page fails silently or only intermittently.
- You need deeper network timing, performance, or live DOM state than the persisted report provides.

## Verification

- Re-run `audit_web` after the fix.
- Use Playwright MCP for the final visual or DOM verification.
- Keep the persisted report paths in the final response when they materially helped the diagnosis.
