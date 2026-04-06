# mcp-cyber

Defensive, **allowlist-gated** security validation for **authorized assets only**.

- **API:** FastAPI (`cyber_api`) — OpenAPI at `/openapi.json`
- **Workers:** scan execution (`cyber_worker`) — invoked from API (background) or CLI
- **MCP:** thin client to API (`cyber_mcp`) — **no autofix**; orchestration and findings only
- **Engine:** passive checks first (`cyber_engine`) — policy gate before any network I/O

## Quick start (local)

```bash
cd mcp/mcp-cyber
python -m venv .venv
.venv\Scripts\activate   # Windows
pip install -e ".[dev]"
copy .env.example .env   # edit DB credentials and CYBER_LISTEN_PORT if needed
docker compose up -d postgres   # optional if you use local Postgres instead
```

Migrations (PowerShell; uses `DATABASE_URL_SYNC` or `CYBER_DATABASE_URL_SYNC`):

```powershell
$env:CYBER_DATABASE_URL_SYNC = "postgres://postgres:admin@127.0.0.1:5432/cyber"
python -m alembic upgrade head
python scripts\seed_demo.py
```

Run the API on **port 8686** (or set `CYBER_LISTEN_PORT` in `.env`):

```powershell
# Loads mcp/mcp-cyber/.env automatically when present
python -m cyber_api
# or: mcp-cyber-api
```

**Dev dashboard:** open [http://127.0.0.1:8686/](http://127.0.0.1:8686/) for an HTML **test console**: **Run passive scan** (uses `POST /dashboard/api/run-scan` — no JWT while the dashboard is on), **security posture** cards (critical / high / medium / low counts and plain-language summaries, plus sample findings), **engine log**, and **audit** tail. Data refreshes every 5 seconds. Exports: `/dashboard/reports/{scan_id}/md|json`. Set `CYBER_DASHBOARD_ENABLED=false` if the API is reachable beyond localhost.

Equivalent manual uvicorn:

```bash
uvicorn cyber_api.main:app --reload --host 127.0.0.1 --port 8686
```

Dev JWT (set `CYBER_DEV_SECRET` or default in `cyber_api.security`):

```http
Authorization: Bearer <token with sub, org_id, role>
```

## Environment variables

| Variable | Description |
|----------|-------------|
| `CYBER_DATABASE_URL` or `DATABASE_URL` | Async PostgreSQL URL for API (`postgresql+asyncpg://…`) |
| `CYBER_DATABASE_URL_SYNC` / `DATABASE_URL_SYNC` | Sync URL for Alembic (`postgres://…` ok) |
| `CYBER_LISTEN_HOST` | API bind host (default `127.0.0.1`) |
| `CYBER_LISTEN_PORT` | API bind port (default `8000`; e.g. `8686`) |
| `CYBER_RELOAD` | `true` — uvicorn auto-reload (dev) |
| `CYBER_REQUIRE_ALLOWLIST` | `true` — enforce target allowlists |
| `CYBER_POLICY_PATH` | Path to `default.policy.yaml` |
| `CYBER_DEV_SECRET` | HS256 secret for dev JWT |
| `CYBER_API_URL` | MCP client base URL (**must match** API host:port) |
| `CYBER_API_TOKEN` | Bearer token for MCP → API |
| `CYBER_DASHBOARD_ENABLED` | `true` (default): serve `/` HTML + `/dashboard/api/*` without JWT; use `false` when exposed |

### Phase 2 (authenticated scans, OIDC, vault, evidence)

| Variable | Description |
|----------|-------------|
| `CYBER_OIDC_ISSUER` | OIDC issuer string (must match JWT `iss`) |
| `CYBER_OIDC_AUDIENCE` | Expected JWT `aud` |
| `CYBER_OIDC_JWKS_URL` | JWKS endpoint for signature verification |
| `CYBER_OIDC_DEFAULT_ORG_ID` | UUID when tokens have no `org_id` claim |
| `CYBER_AUTH_DEV_JWT_ALONGSIDE_OIDC` | `true` (default): allow HS256 dev JWT when OIDC is configured; set `false` in prod |
| `CYBER_VAULT_JSON` / `CYBER_VAULT_FILE` | Map `credential_ref` → `username` / `password` / `token` (JSON object) |
| `VAULT_ADDR` + `VAULT_TOKEN` + `CYBER_VAULT_KV_MOUNT` | Optional HashiCorp KV v2 (`…/v1/{mount}/data/{ref}`) |
| `CYBER_PLAYWRIGHT_ADAPTER` | `1` / `true` to allow password-based browser login in `playwright_session` adapter |
| `CYBER_EVIDENCE_DIR` | Directory for screenshot artifacts (`file:…` URIs on findings) |

**Scan profile (mode `authenticated_passive`):** set `credential_ref` to a vault key; add options such as `playwright_login_url`, `playwright_username_selector`, `playwright_password_selector`, `playwright_submit_selector`. The login URL must be allowlisted for the environment (included in policy checks). If the vault entry has only `token`, the adapter performs Bearer-authenticated passive checks without Playwright. Install browser support: `pip install -e ".[phase2]"` and `playwright install chromium`.

### Phase 3 (enterprise workflow, queue, suppressions)

| Piece | Description |
|-------|-------------|
| **`CYBER_REDIS_URL`** | If set, new scans are **enqueued** on Redis list `cyber:scans:queue` instead of only `BackgroundTasks`. Run **`python -m cyber_worker.consumer`** or **`mcp-cyber-scan-worker`** (requires `pip install -e ".[phase3]"`). If LPUSH fails, the API falls back to in-process execution. |
| **Approvals** | `POST /v1/approvals` for an **`active_controlled`** profile → `approve` / `reject` by `security_engineer` or `admin`. Requires a **`users`** row matching your JWT email (see `scripts/seed_demo.py`). |
| **`active_controlled_stub`** | Adapter id for safe Phase 3 runs: records an **info** finding only (no intrusive payloads). Add it to the profile’s `adapter_ids` with mode `active_controlled` and pass `approval_id` on `POST /v1/scans`. |
| **Suppressions** | `POST /v1/suppressions` (fingerprint + project) — worker **skips persisting** matching findings on future scans. |
| **Finding status** | `POST /v1/findings/{id}/transition` — allowed: `open`, `in_progress`, `accepted_risk`, `fixed`, `suppressed`, `regressed`. |
| **Docker** | `redis` service is defined; **`scan-worker`** uses Compose **profile** `ha-scans` (`docker compose --profile ha-scans up`). Set `CYBER_REDIS_URL` on **api** when using the queue. |

**MCP (Phase 3 tools):** `run_controlled_active_scan`, `request_scan_approval`, `list_approvals`, `approve_scan_approval`, `reject_scan_approval`, `get_audit_log`, `mark_finding_status`, `create_suppression`, `list_suppressions`, `delete_suppression`.

### Phase 4 (analytics, trends, SLA signals)

| API | Description |
|-----|-------------|
| `GET /v1/analytics/fleet` | Per-project **open** counts by severity (org-scoped). |
| `GET /v1/analytics/trends/findings?days=30` | Daily buckets of **first_seen_at** volume by severity (PostgreSQL `to_char`). |
| `GET /v1/analytics/trends/scans?days=30` | Successful **scan runs** completed per day. |
| `GET /v1/analytics/sla/open-high-critical` | **manager+**: open **critical/high** older than `CYBER_SLA_HIGH_CRITICAL_DAYS` (optional `max_age_days`). |
| `GET /v1/analytics/top-rules?limit=20` | Top **rule_id** values among open findings. |

| Setting | Default | Purpose |
|---------|---------|---------|
| `CYBER_SLA_HIGH_CRITICAL_DAYS` | `14` | SLA window for breach detection. |
| `CYBER_ANALYTICS_MAX_TREND_DAYS` | `90` | Cap for `days` on trend endpoints (still pass `days=` query; server clamps). |

**MCP (Phase 4):** `get_fleet_posture`, `get_finding_trends`, `get_scan_volume_trends`, `get_sla_breach_summary`, `get_top_open_rules`.

*Future (not implemented here):* fleet HTML dashboards, custom YAML business-rule engines, allowlisted fuzzing harness hooks — see platform plan Phase 4.

Optional: `mcp/mcp-cyber/.env` — see `.env.example`; `python -m cyber_api` loads it when the file exists.

## MCP (Cursor / Claude)

Run the stdio server (after `pip install -e .`):

```bash
set CYBER_API_URL=http://127.0.0.1:8686
set CYBER_API_TOKEN=<jwt from scripts/seed_demo.py>
python -m cyber_mcp.server
```

Or use the console script `mcp-cyber-mcp` if your `Scripts` directory is on PATH.

Tools include passive/authenticated scans, reports, compare, Phase 3 approvals/suppressions/audit (`README` Phase 3 table).

Example `.cursor/mcp.json` fragment:

```json
{
  "mcpServers": {
    "mcp-cyber": {
      "command": "python",
      "args": ["-m", "cyber_mcp.server"],
      "cwd": "mcp/mcp-cyber",
      "env": {
        "CYBER_API_URL": "http://127.0.0.1:8686",
        "CYBER_API_TOKEN": "YOUR_JWT"
      }
    }
  }
}
```

## Scan a remote site you control (e.g. production HTTPS)

With the API running and `CYBER_API_TOKEN` set (JWT from `python scripts/seed_demo.py`):

```powershell
cd mcp/mcp-cyber
# Optional: CYBER_API_URL and CYBER_API_TOKEN in .env
python scripts/quick_scan_remote.py --base-url https://plm.pekofactory.store/
```

This creates (or reuses) an **environment** with an allowlist for that host, a **passive** scan profile, starts a scan, and prints report URLs. Options: `--env-name`, `--project-slug`, `--target-urls "https://a/,https://a/login"`, `--register-only`.

## Safety

This software is for **testing systems you own or control**. Do not point it at third-party targets. **Review-required remediation** — the platform does not mutate your application code or infrastructure.
