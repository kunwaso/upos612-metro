"""mcp-cyber FastAPI control plane."""

from __future__ import annotations

import os
from contextlib import asynccontextmanager
from pathlib import Path
from uuid import UUID

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

# Configure DB URL before importing modules that create the engine
from cyber_api.settings import settings

os.environ.setdefault("DATABASE_URL", settings.database_url)
if settings.policy_path:
    os.environ.setdefault("CYBER_POLICY_PATH", settings.policy_path)
if settings.require_allowlist is not None:
    os.environ["CYBER_REQUIRE_ALLOWLIST"] = "true" if settings.require_allowlist else "false"

from cyber_api.logging_setup import setup_app_logging
from cyber_api.routers import (
    analytics,
    approvals,
    audit,
    dashboard,
    environments,
    findings,
    openapi_compare,
    profiles,
    projects,
    reports,
    scans,
    suppressions,
)
from cyber_api.security import create_dev_token
from cyber_core.logging import configure_logging


def _parse_cors_origins(raw: str) -> list[str]:
    return [o.strip() for o in raw.split(",") if o.strip()]


def _cors_allow_credentials(origins: list[str]) -> bool:
    env = os.environ.get("CYBER_CORS_ALLOW_CREDENTIALS", "").strip().lower()
    if env in ("1", "true", "yes"):
        return "*" not in origins
    if env in ("0", "false", "no"):
        return False
    return False


@asynccontextmanager
async def lifespan(app: FastAPI):
    Path(settings.artifacts_dir).mkdir(parents=True, exist_ok=True)
    configure_logging(json_logs=True, level=os.environ.get("CYBER_LOG_LEVEL", "INFO"))
    yield


app = FastAPI(
    title="mcp-cyber API",
    version="0.1.0",
    openapi_url="/openapi.json",
    lifespan=lifespan,
)
setup_app_logging(app)
_cors_origins = _parse_cors_origins(os.environ.get("CYBER_CORS_ORIGINS", ""))
app.add_middleware(
    CORSMiddleware,
    allow_origins=_cors_origins,
    allow_credentials=_cors_allow_credentials(_cors_origins),
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(dashboard.router)
app.include_router(projects.router)
app.include_router(environments.router)
app.include_router(openapi_compare.router)
app.include_router(profiles.router)
app.include_router(scans.router)
app.include_router(approvals.router)
app.include_router(suppressions.router)
app.include_router(findings.router)
app.include_router(reports.router)
app.include_router(analytics.router)
app.include_router(audit.router)


@app.get("/health")
async def health():
    return {"status": "ok", "service": "mcp-cyber"}


@app.post("/v1/dev/seed-token")
async def dev_seed_token():
    """Local-only helper: returns a JWT when CYBER_ALLOW_DEV_SEED=true."""
    if os.environ.get("CYBER_ALLOW_DEV_SEED", "").lower() != "true":
        from fastapi import HTTPException, status

        raise HTTPException(status.HTTP_403_FORBIDDEN, "Disabled")
    org = UUID("00000000-0000-4000-8000-000000000001")
    user = UUID("00000000-0000-4000-8000-000000000002")
    token = create_dev_token(
        user_id=str(user),
        org_id=org,
        email="dev@local.test",
        role="admin",
    )
    return {"access_token": token, "token_type": "bearer", "hint": "Create org/user rows via scripts/seed_demo.py"}
