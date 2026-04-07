"""
Create demo org, user, project, environment, and scan profile for local testing.

Usage (from mcp-cyber root, with Postgres running):
  python scripts/seed_demo.py

Loads ``mcp/mcp-cyber/.env`` when present (same as quick_scan_remote). Uses
``DATABASE_URL`` or, if unset, ``CYBER_DATABASE_URL`` from that file. The async
engine only reads ``DATABASE_URL`` (see cyber_db.session).
"""

from __future__ import annotations

import asyncio
import os
import sys
import uuid
from pathlib import Path

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, ROOT)


def _load_env_file(path: Path) -> None:
    if not path.is_file():
        return
    for raw in path.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if not line or line.startswith("#"):
            continue
        if line.startswith("export "):
            line = line[7:].strip()
        if "=" not in line:
            continue
        key, _, val = line.partition("=")
        key = key.strip()
        val = val.strip().strip("'\"")
        if key and key not in os.environ:
            os.environ[key] = val


_load_env_file(Path(ROOT) / ".env")
if not os.environ.get("DATABASE_URL") and os.environ.get("CYBER_DATABASE_URL"):
    os.environ["DATABASE_URL"] = os.environ["CYBER_DATABASE_URL"]
os.environ.setdefault("DATABASE_URL", "postgresql+asyncpg://cyber:cyber@127.0.0.1:5432/cyber")

from sqlalchemy import select

from cyber_api.security import create_dev_token
from cyber_db.models import Environment, Organization, Project, ScanProfile, User
from cyber_db.session import async_session_factory

ORG_ID = uuid.UUID("00000000-0000-4000-8000-000000000001")
USER_ID = uuid.UUID("00000000-0000-4000-8000-000000000002")


async def main() -> None:
    async with async_session_factory() as session:
        if not await session.get(Organization, ORG_ID):
            session.add(Organization(id=ORG_ID, name="Demo Org"))
        if not await session.get(User, USER_ID):
            session.add(
                User(id=USER_ID, org_id=ORG_ID, email="dev@local.test", role="admin")
            )

        proj = await session.scalar(select(Project).where(Project.org_id == ORG_ID, Project.slug == "demo"))
        if not proj:
            proj = Project(org_id=ORG_ID, slug="demo", name="Demo Project", owner_team="platform")
            session.add(proj)
            await session.flush()

        env = await session.scalar(
            select(Environment).where(Environment.project_id == proj.id, Environment.name == "local")
        )
        if not env:
            env = Environment(
                project_id=proj.id,
                name="local",
                env_class="local",
                base_url="http://127.0.0.1:8000",
                allowlist={"hosts": ["127.0.0.1", "localhost"], "path_prefixes": ["/"]},
            )
            session.add(env)
            await session.flush()

        prof = await session.scalar(
            select(ScanProfile).where(
                ScanProfile.environment_id == env.id, ScanProfile.name == "default-passive"
            )
        )
        if not prof:
            session.add(
                ScanProfile(
                    environment_id=env.id,
                    name="default-passive",
                    mode="passive",
                    adapter_ids=[
                        "headers_cookies",
                        "tls_basic",
                        "openapi_lint",
                        "routes_json_lint",
                    ],
                    rate_limit_rps=2,
                    max_concurrency=2,
                    options={},
                )
            )

        await session.commit()

    async with async_session_factory() as s2:
        env = await s2.scalar(
            select(Environment)
            .join(Project)
            .where(Project.org_id == ORG_ID, Environment.name == "local")
        )
        prof = await s2.scalar(
            select(ScanProfile)
            .join(Environment)
            .join(Project)
            .where(Project.org_id == ORG_ID, ScanProfile.name == "default-passive")
        )

    if env is None or prof is None:
        raise SystemExit("Seed failed: could not resolve environment or scan profile.")

    token = create_dev_token(user_id=str(USER_ID), org_id=ORG_ID, email="dev@local.test", role="admin")
    print("Seeded demo data.")
    print("CYBER_API_TOKEN:")
    print(token)
    print("scan_profile_id:", prof.id)


if __name__ == "__main__":
    asyncio.run(main())
