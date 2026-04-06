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

Optional: `mcp/mcp-cyber/.env` — see `.env.example`; `python -m cyber_api` loads it when the file exists.

## MCP (Cursor / Claude)

Run the stdio server (after `pip install -e .`):

```bash
set CYBER_API_URL=http://127.0.0.1:8686
set CYBER_API_TOKEN=<jwt from scripts/seed_demo.py>
python -m cyber_mcp.server
```

Or use the console script `mcp-cyber-mcp` if your `Scripts` directory is on PATH.

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
