"""
Comprehensive health-check: DB connectivity, schema, migrations, settings, adapters, MCP tools.
Loads .env and sets DATABASE_URL before importing session so the engine picks up the right URL.
"""
import os
from pathlib import Path

# Load .env values into os.environ BEFORE any cyber_db.session import
_env_path = Path(__file__).resolve().parent / ".env"
if _env_path.is_file():
    for _line in _env_path.read_text(encoding="utf-8").splitlines():
        _line = _line.strip()
        if not _line or _line.startswith("#") or "=" not in _line:
            continue
        _k, _, _v = _line.partition("=")
        _k = _k.strip(); _v = _v.strip()
        if _k and _v:
            os.environ.setdefault(_k, _v)

# Bridge: session.py reads DATABASE_URL directly; settings reads CYBER_DATABASE_URL
_db = (
    os.environ.get("CYBER_DATABASE_URL")
    or os.environ.get("DATABASE_URL")
    or "postgresql+asyncpg://cyber:cyber@127.0.0.1:5432/cyber"
)
os.environ.setdefault("DATABASE_URL", _db)

# NOW import cyber packages (session engine is created at import time)
import asyncio
from sqlalchemy import text
from cyber_db.session import async_session_factory
from cyber_api.settings import Settings
from cyber_engine.orchestrator import DEFAULT_ADAPTERS
from cyber_mcp.server import mcp

EXPECTED_TABLES = [
    "alembic_version", "approvals", "assets", "audit_log", "environments",
    "findings", "openapi_artifacts", "organizations", "projects",
    "routes_artifacts", "scan_events", "scan_profiles", "scan_runs",
    "suppressions", "users",
]

SEP = "-" * 60

def section(title: str) -> None:
    print(f"\n{SEP}\n{title}\n{SEP}")


async def check() -> None:
    # ── Settings ─────────────────────────────────────────────
    section("1. Settings")
    cfg = Settings()
    print(f"  database_url          = {cfg.database_url[:55]}...")
    print(f"  listen_host:port      = {cfg.listen_host}:{cfg.listen_port}")
    print(f"  dashboard_enabled     = {cfg.dashboard_enabled}")
    print(f"  policy_path           = {cfg.policy_path or '(none — default permissive)'}")
    print(f"  redis_url             = {repr(cfg.redis_url) if cfg.redis_url else '(none — BackgroundTasks mode)'}")
    print(f"  oidc_issuer           = {repr(cfg.oidc_issuer) if cfg.oidc_issuer else '(none — dev JWT only)'}")
    print(f"  sla_high_critical_days= {cfg.sla_high_critical_days}")
    print(f"  analytics_max_trend   = {cfg.analytics_max_trend_days}")
    print(f"  business_rules_path   = {cfg.business_rules_path or '(none — no YAML tag rules)'}")

    # ── Adapters ─────────────────────────────────────────────
    section("2. Registered Adapters")
    for k in sorted(DEFAULT_ADAPTERS):
        print(f"  {k}")

    # ── MCP tools ────────────────────────────────────────────
    section("3. MCP Tools")
    tools = sorted(t.name for t in mcp._tool_manager.list_tools())
    for t in tools:
        print(f"  {t}")
    print(f"\n  Total: {len(tools)}")

    # ── DB / schema ──────────────────────────────────────────
    section("4. Database & Schema")
    try:
        async with async_session_factory() as s:
            v = await s.execute(text("SELECT version()"))
            print("  PostgreSQL:", str(v.scalar())[:60])

            t = await s.execute(
                text("SELECT tablename FROM pg_tables WHERE schemaname='public' ORDER BY tablename")
            )
            tables = [r[0] for r in t.all()]
            print(f"\n  Tables ({len(tables)}):")
            for tbl in tables:
                mark = " OK " if tbl in EXPECTED_TABLES else " ???"
                print(f"    [{mark}] {tbl}")

            missing = [x for x in EXPECTED_TABLES if x not in tables]
            if missing:
                print(f"\n  [!] MISSING: {missing}")
                print("      Run: python -m alembic upgrade head")
            else:
                print("\n  [OK] All expected tables present")

            av = await s.execute(text("SELECT version_num FROM alembic_version"))
            revs = [r[0] for r in av.all()]
            print(f"  Alembic revision(s): {revs}")
            needs_003 = not any("003" in str(r) for r in revs)
            if needs_003:
                print("  [!] Migration 003 (routes_artifacts) NOT applied — run: python -m alembic upgrade head")
            else:
                print("  [OK] Migration 003 applied")

            # ── Row counts ───────────────────────────────────
            print("\n  Row counts:")
            for tbl in ["organizations", "users", "scan_profiles", "scan_runs",
                         "findings", "suppressions", "approvals"]:
                if tbl in tables:
                    c = await s.execute(text(f"SELECT COUNT(*) FROM {tbl}"))
                    print(f"    {tbl:<22} = {c.scalar()}")

    except Exception as e:
        print(f"  [!] DB ERROR: {type(e).__name__}: {e}")

    # ── API health (live check if running) ───────────────────
    section("5. API Live Check (http://127.0.0.1:" + str(cfg.listen_port) + "/health)")
    try:
        import httpx
        async with httpx.AsyncClient(timeout=5) as client:
            r = await client.get(f"http://127.0.0.1:{cfg.listen_port}/health")
            print(f"  HTTP {r.status_code}: {r.json()}")
    except Exception as e:
        print(f"  [!] API not reachable: {type(e).__name__} — start with: python -m cyber_api")

    section("Done")


asyncio.run(check())
