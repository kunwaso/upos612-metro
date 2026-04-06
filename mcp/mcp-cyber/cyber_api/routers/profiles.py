from __future__ import annotations

from typing import Annotated
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select

from cyber_api.audit import write_audit
from cyber_api.deps import DbSession
from cyber_api.principals import actor_uuid
from cyber_api.schemas import ScanProfileCreate, ScanProfileOut
from cyber_api.security import TokenUser, get_current_user
from cyber_db.models import Environment, Project, ScanProfile

router = APIRouter(prefix="/v1/environments", tags=["scan-profiles"])


@router.post("/{environment_id}/scan-profiles", response_model=ScanProfileOut, status_code=status.HTTP_201_CREATED)
async def create_profile(
    environment_id: UUID,
    body: ScanProfileCreate,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    env = await session.get(Environment, environment_id)
    if not env:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Environment not found")
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Environment not found")
    sp = ScanProfile(
        environment_id=environment_id,
        name=body.name,
        mode=body.mode,
        adapter_ids=body.adapter_ids,
        rate_limit_rps=body.rate_limit_rps,
        max_concurrency=body.max_concurrency,
        credential_ref=body.credential_ref,
        options=body.options,
    )
    session.add(sp)
    await write_audit(
        session,
        actor_id=actor_uuid(user),
        action="scan_profile.create",
        object_type="scan_profile",
        object_id=str(sp.id),
        payload={"name": body.name, "mode": body.mode},
    )
    await session.commit()
    await session.refresh(sp)
    return sp


@router.get("/{environment_id}/scan-profiles", response_model=list[ScanProfileOut])
async def list_profiles(
    environment_id: UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    env = await session.get(Environment, environment_id)
    if not env:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Environment not found")
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Environment not found")
    res = await session.execute(select(ScanProfile).where(ScanProfile.environment_id == environment_id))
    return list(res.scalars().all())
