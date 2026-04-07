from __future__ import annotations

import hashlib
import uuid as u
from pathlib import Path
from typing import Annotated
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select

from cyber_api.audit import write_audit
from cyber_api.deps import DbSession
from cyber_api.schemas import EnvironmentCreate, EnvironmentOut, OpenAPIImport, RoutesImport
from cyber_api.principals import actor_uuid
from cyber_api.security import TokenUser, get_current_user
from cyber_api.settings import settings
from cyber_db.models import Environment, OpenAPIArtifact, Project, RoutesArtifact

router = APIRouter(prefix="/v1", tags=["environments"])


@router.post("/projects/{project_id}/environments", response_model=EnvironmentOut, status_code=status.HTTP_201_CREATED)
async def create_environment(
    project_id: UUID,
    body: EnvironmentCreate,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    proj = await session.get(Project, project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Project not found")
    env = Environment(
        project_id=project_id,
        name=body.name,
        env_class=body.env_class,
        base_url=body.base_url.rstrip("/"),
        allowlist=body.allowlist or {},
    )
    session.add(env)
    await write_audit(
        session,
        actor_id=actor_uuid(user),
        action="environment.create",
        object_type="environment",
        object_id=str(env.id),
        payload={"name": body.name},
    )
    await session.commit()
    await session.refresh(env)
    return env


@router.get("/projects/{project_id}/environments", response_model=list[EnvironmentOut])
async def list_environments(
    project_id: UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    proj = await session.get(Project, project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Project not found")
    res = await session.execute(select(Environment).where(Environment.project_id == project_id))
    return list(res.scalars().all())


@router.post(
    "/environments/{environment_id}/openapi",
    status_code=status.HTTP_201_CREATED,
)
async def import_openapi(
    environment_id: UUID,
    body: OpenAPIImport,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    env = await session.get(Environment, environment_id)
    if not env:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Environment not found")
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Environment not found")
    raw = body.spec_json.encode("utf-8")
    sha = hashlib.sha256(raw).hexdigest()
    base = Path(settings.artifacts_dir)
    base.mkdir(parents=True, exist_ok=True)
    aid = u.uuid4()
    path = base / f"{aid}.json"
    path.write_text(body.spec_json, encoding="utf-8")
    art = OpenAPIArtifact(
        id=aid,
        environment_id=environment_id,
        version=body.version,
        storage_uri=str(path.resolve()),
        sha256=sha,
    )
    session.add(art)
    await write_audit(
        session,
        actor_id=actor_uuid(user),
        action="openapi.import",
        object_type="openapi_artifact",
        object_id=str(art.id),
        payload={"sha256": sha},
    )
    await session.commit()
    return {"openapi_artifact_id": str(art.id), "sha256": sha}


@router.post(
    "/environments/{environment_id}/routes-json",
    status_code=status.HTTP_201_CREATED,
)
async def import_routes_json(
    environment_id: UUID,
    body: RoutesImport,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    """Upload CI-exported route list JSON (RBAC lint). Same storage layout as OpenAPI artifacts."""
    env = await session.get(Environment, environment_id)
    if not env:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Environment not found")
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Environment not found")
    raw = body.routes_json.encode("utf-8")
    sha = hashlib.sha256(raw).hexdigest()
    base = Path(settings.artifacts_dir)
    base.mkdir(parents=True, exist_ok=True)
    rid = u.uuid4()
    path = base / f"routes-{rid}.json"
    path.write_text(body.routes_json, encoding="utf-8")
    art = RoutesArtifact(
        id=rid,
        environment_id=environment_id,
        label=body.label,
        storage_uri=str(path.resolve()),
        sha256=sha,
    )
    session.add(art)
    await write_audit(
        session,
        actor_id=actor_uuid(user),
        action="routes_json.import",
        object_type="routes_artifact",
        object_id=str(art.id),
        payload={"sha256": sha, "label": body.label},
    )
    await session.commit()
    return {"routes_artifact_id": str(art.id), "sha256": sha}
