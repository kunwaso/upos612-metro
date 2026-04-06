from __future__ import annotations

import uuid
from datetime import datetime, timezone
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select

from cyber_api.audit import write_audit
from cyber_api.deps import DbSession
from cyber_api.principals import actor_uuid
from cyber_api.schemas import FindingOut, FindingTransition
from cyber_api.security import TokenUser, get_current_user
from cyber_db.models import Environment, Finding, Project, ScanProfile, ScanRun

router = APIRouter(prefix="/v1/findings", tags=["findings"])


@router.get("/{finding_id}", response_model=FindingOut)
async def get_finding(
    finding_id: uuid.UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    f = await session.get(Finding, finding_id)
    if not f:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Finding not found")
    proj = await session.get(Project, f.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Finding not found")
    return FindingOut.from_orm_finding(f)


@router.post("/{finding_id}/transition", response_model=FindingOut)
async def transition_finding(
    finding_id: uuid.UUID,
    body: FindingTransition,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    f = await session.get(Finding, finding_id)
    if not f:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Finding not found")
    proj = await session.get(Project, f.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Finding not found")

    if body.status in ("accepted_risk", "suppressed") and user.role not in ("security_engineer", "admin"):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Role cannot set this status")

    f.status = body.status
    if body.status == "fixed":
        f.fixed_at = datetime.now(timezone.utc)
    await write_audit(
        session,
        actor_id=actor_uuid(user),
        action="finding.transition",
        object_type="finding",
        object_id=str(f.id),
        payload={"status": body.status, "reason": body.reason},
    )
    await session.commit()
    await session.refresh(f)
    return FindingOut.from_orm_finding(f)
