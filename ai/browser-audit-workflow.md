# Browser Audit Workflow

Use this workflow when the user asks for `audit and fix: <url>` or `interactive web audit: <url>`.

## Prompt Contract

- `audit and fix: <url>`: run an interactive audit first, persist findings, inspect code, fix the issue, then verify with Playwright MCP and a rerun of `audit_web`.
- `interactive web audit: <url>`: run the interactive audit and return the findings plus next-step recommendation without making code changes unless the user also asked for a fix.

## Auth-First Rules (Protected Pages)

- Reuse cookie-backed storage state whenever possible: `output/playwright/audit-web-mcp/reports/.auth/pos-admin.json`.
- For authenticated URLs, pass `storage_state_path` to `audit_web` before investigating UI findings.
- If the page redirects to `/login`, or findings indicate session/auth failure, refresh auth immediately with:
  - `login_url: <baseUrl>/login`
  - `login_username: admin`
  - `login_password: admin123`
  - `save_storage_state_path: output/playwright/audit-web-mcp/reports/.auth/pos-admin.json`
- Do not treat unauthenticated findings on protected pages as valid bug evidence; rerun after auth succeeds.

## Tool Order

1. Start the dedicated Chrome debug browser with `[scripts/open-audit-chrome.ps1](/d:/wamp64/www/upos612/scripts/open-audit-chrome.ps1)`.
2. Ensure auth state is ready:
  - Use `storage_state_path=output/playwright/audit-web-mcp/reports/.auth/pos-admin.json` when it exists.
  - If state is missing/expired, run one auth bootstrap `audit_web` call using `login_url=/login`, `login_username=admin`, `login_password=admin123`, and `save_storage_state_path`.
3. Run `audit_web` in `mode=interactive` with `persist_report=true`.
4. Read `report.json` / `report.md` from `output/playwright/audit-web-mcp/reports/<session>/`.
5. If findings are empty, ambiguous, timing-sensitive, or performance-related, escalate to Chrome DevTools MCP.
6. Fix the issue in the repo.
7. Verify with Playwright MCP snapshot/screenshot and a rerun of `audit_web` in the same logged-in browser context when possible.

## Persistence Rules

- Persist interactive runs under `output/playwright/audit-web-mcp/reports/<timestamp>-<slug>/`.
- Keep `report.json`, `report.md`, screenshot, trace, and optional `storage-state.json` together in the session directory.
- Update `latest.json` and `latest.md` after each persisted audit so agents can resume quickly.
- Keep reusable auth cookies at `output/playwright/audit-web-mcp/reports/.auth/pos-admin.json`.

## Escalate To Chrome DevTools MCP When

- `triageSummary.shouldEscalateToDevtools` is `true`.
- The page fails silently or only intermittently.
- You need deeper network timing, performance, or live DOM state than the persisted report provides.

## Verification

- Re-run `audit_web` after the fix.
- Use Playwright MCP for the final visual or DOM verification, and login first if redirected to `/login`.
- Keep the persisted report paths in the final response when they materially helped the diagnosis.
