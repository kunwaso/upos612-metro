"""Controlled-active scan approvals (Phase 3 enterprise workflow)."""

from __future__ import annotations

import uuid
from datetime import datetime, timedelta, timezone
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from cyber_api.audit import write_audit
from cyber_api.auth_users import require_db_user_id
from cyber_api.deps import DbSession
from cyber_api.schemas import ApprovalCreate, ApprovalOut, ApprovalResolve
from cyber_api.security import TokenUser, get_current_user
from cyber_db.models import Approval, Environment, Project, ScanProfile

router = APIRouter(prefix="/v1/approvals", tags=["approvals"])


def _can_request(user: TokenUser) -> bool:
    return user.role in ("developer", "security_engineer", "manager", "admin")


def _can_approve(user: TokenUser) -> bool:
    return user.role in ("security_engineer", "admin")


async def _auth_profile_org(session: DbSession, profile_id: uuid.UUID, org_id: uuid.UUID) -> ScanProfile:
    profile = await session.get(ScanProfile, profile_id)
    if not profile:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Profile not found")
    env = await session.get(Environment, profile.environment_id)
    if not env:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Profile not found")
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Profile not found")
    return profile


@router.get("", response_model=list[ApprovalOut])
async def list_approvals(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    status_filter: str | None = Query(None, alias="status", description="pending|approved|rejected"),
    limit: int = Query(50, ge=1, le=200),
):
    if user.role not in ("security_engineer", "admin", "manager"):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Insufficient role")
    stmt = (
        select(Approval)
        .join(ScanProfile, Approval.profile_id == ScanProfile.id)
        .join(Environment, ScanProfile.environment_id == Environment.id)
        .join(Project, Environment.project_id == Project.id)
        .where(Project.org_id == user.org_id)
        .order_by(Approval.created_at.desc())
        .limit(limit)
    )
    if status_filter:
        sf = status_filter.strip().lower()
        if sf not in ("pending", "approved", "rejected"):
            raise HTTPException(status.HTTP_400_BAD_REQUEST, "Invalid status filter")
        stmt = stmt.where(Approval.status == sf)
    res = await session.execute(stmt)
    rows = res.scalars().all()
    return [ApprovalOut.model_validate(a) for a in rows]


@router.post("", response_model=ApprovalOut, status_code=status.HTTP_201_CREATED)
async def create_approval(
    body: ApprovalCreate,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    if not _can_request(user):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Insufficient role")
    profile = await _auth_profile_org(session, body.profile_id, user.org_id)
    if profile.mode != "active_controlled":
        raise HTTPException(
            status.HTTP_400_BAD_REQUEST,
            "Approvals apply to profiles in active_controlled mode.",
        )
    requester_id = await require_db_user_id(session, user)
    now = datetime.now(timezone.utc)
    expires = now + timedelta(hours=body.expires_in_hours)
    ap = Approval(
        profile_id=body.profile_id,
        requester_id=requester_id,
        status="pending",
        reason=body.reason,
        payload_tier=body.payload_tier,
        expires_at=expires,
    )
    session.add(ap)
    await write_audit(
        session,
        actor_id=requester_id,
        action="approval.request",
        object_type="approval",
        object_id=str(ap.id),
        payload={"profile_id": str(body.profile_id)},
    )
    await session.commit()
    await session.refresh(ap)
    return ApprovalOut.model_validate(ap)


@router.post("/{approval_id}/approve", response_model=ApprovalOut)
async def approve_approval(
    approval_id: uuid.UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    body: ApprovalResolve | None = None,
):
    if not _can_approve(user):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Only security_engineer or admin can approve")
    ap = await session.get(Approval, approval_id)
    if not ap:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Approval not found")
    await _auth_profile_org(session, ap.profile_id, user.org_id)
    if ap.status != "pending":
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Approval is not pending")
    now = datetime.now(timezone.utc)
    if ap.expires_at and ap.expires_at < now:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Approval has expired")
    approver_id = await require_db_user_id(session, user)
    if approver_id == ap.requester_id and user.role != "admin":
        raise HTTPException(
            status.HTTP_403_FORBIDDEN,
            "Approver cannot be the same as requester unless role is admin",
        )
    ap.status = "approved"
    ap.approver_id = approver_id
    if body and body.note:
        ap.reason = (ap.reason or "") + ("\n[approver] " if ap.reason else "[approver] ") + body.note
    await write_audit(
        session,
        actor_id=approver_id,
        action="approval.approve",
        object_type="approval",
        object_id=str(ap.id),
        payload={},
    )
    await session.commit()
    await session.refresh(ap)
    return ApprovalOut.model_validate(ap)


@router.post("/{approval_id}/reject", response_model=ApprovalOut)
async def reject_approval(
    approval_id: uuid.UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    body: ApprovalResolve | None = None,
):
    if not _can_approve(user):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Only security_engineer or admin can reject")
    ap = await session.get(Approval, approval_id)
    if not ap:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Approval not found")
    await _auth_profile_org(session, ap.profile_id, user.org_id)
    if ap.status != "pending":
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "Approval is not pending")
    approver_id = await require_db_user_id(session, user)
    ap.status = "rejected"
    ap.approver_id = approver_id
    note = (body.note if body else None) or "rejected"
    ap.reason = (ap.reason or "") + ("\n[reject] " if ap.reason else "[reject] ") + note
    await write_audit(
        session,
        actor_id=approver_id,
        action="approval.reject",
        object_type="approval",
        object_id=str(ap.id),
        payload={},
    )
    await session.commit()
    await session.refresh(ap)
    return ApprovalOut.model_validate(ap)
