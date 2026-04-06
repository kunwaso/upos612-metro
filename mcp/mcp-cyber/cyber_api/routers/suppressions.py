"""Finding suppressions by fingerprint (Phase 3 — CI / triage)."""

from __future__ import annotations

import uuid
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select

from cyber_api.audit import write_audit
from cyber_api.auth_users import require_db_user_id
from cyber_api.deps import DbSession
from cyber_api.principals import actor_uuid
from cyber_api.schemas import SuppressionCreate, SuppressionOut
from cyber_api.security import TokenUser, get_current_user
from cyber_db.models import Project, Suppression

router = APIRouter(prefix="/v1/suppressions", tags=["suppressions"])


async def _auth_project(session: DbSession, project_id: uuid.UUID, org_id: uuid.UUID) -> Project:
    proj = await session.get(Project, project_id)
    if not proj or proj.org_id != org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Project not found")
    return proj


@router.get("", response_model=list[SuppressionOut])
async def list_suppressions(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    project_id: uuid.UUID = Query(..., description="Scope to one project"),
    limit: int = Query(100, ge=1, le=500),
):
    await _auth_project(session, project_id, user.org_id)
    res = await session.execute(
        select(Suppression)
        .where(Suppression.project_id == project_id)
        .order_by(Suppression.created_at.desc())
        .limit(limit)
    )
    return [SuppressionOut.model_validate(s) for s in res.scalars().all()]


@router.post("", response_model=SuppressionOut, status_code=status.HTTP_201_CREATED)
async def create_suppression(
    body: SuppressionCreate,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    if user.role not in ("security_engineer", "admin"):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Only security_engineer or admin can create suppressions")
    await _auth_project(session, body.project_id, user.org_id)
    uid = await require_db_user_id(session, user)
    sup = Suppression(
        project_id=body.project_id,
        fingerprint=body.fingerprint.strip(),
        reason=body.reason.strip(),
        created_by=uid,
        expires_at=body.expires_at,
    )
    session.add(sup)
    await write_audit(
        session,
        actor_id=uid,
        action="suppression.create",
        object_type="suppression",
        object_id=str(sup.id),
        payload={"project_id": str(body.project_id), "fingerprint": body.fingerprint[:80]},
    )
    await session.commit()
    await session.refresh(sup)
    return SuppressionOut.model_validate(sup)


@router.delete("/{suppression_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_suppression(
    suppression_id: uuid.UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    if user.role not in ("security_engineer", "admin"):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Insufficient role")
    sup = await session.get(Suppression, suppression_id)
    if not sup:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Suppression not found")
    await _auth_project(session, sup.project_id, user.org_id)
    actor = actor_uuid(user)
    await write_audit(
        session,
        actor_id=actor,
        action="suppression.delete",
        object_type="suppression",
        object_id=str(sup.id),
        payload={},
    )
    await session.delete(sup)
    await session.commit()
    return None
