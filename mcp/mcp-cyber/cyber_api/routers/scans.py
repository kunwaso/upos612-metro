from __future__ import annotations

import uuid
from typing import Annotated

from fastapi import APIRouter, BackgroundTasks, Depends, HTTPException, status
from sqlalchemy import select

from cyber_api.audit import write_audit
from cyber_api.deps import DbSession
from cyber_api.principals import actor_uuid
from cyber_api.schemas import CompareOut, FindingOut, ScanCreate, ScanOut
from cyber_api.security import TokenUser, get_current_user
from cyber_api.settings import settings
from cyber_db.models import (
    Approval,
    Environment,
    Finding,
    OpenAPIArtifact,
    Project,
    RoutesArtifact,
    ScanProfile,
    ScanRun,
)
from cyber_worker.redis_jobs import enqueue_scan
from cyber_worker.tasks import execute_scan

router = APIRouter(prefix="/v1/scans", tags=["scans"])


@router.post("", response_model=ScanOut, status_code=status.HTTP_202_ACCEPTED)
async def create_scan(
    body: ScanCreate,
    background_tasks: BackgroundTasks,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    if body.idempotency_key:
        existing = await session.scalar(select(ScanRun).where(ScanRun.idempotency_key == body.idempotency_key))
        if existing:
            return existing

    profile = await session.get(ScanProfile, body.profile_id)
    if not profile:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Profile not found")
    env = await session.get(Environment, profile.environment_id)
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Profile not found")

    trace_id = str(uuid.uuid4())
    opts: dict = {}
    if body.target_urls:
        opts["target_urls"] = body.target_urls
    if body.openapi_artifact_id:
        oart = await session.get(OpenAPIArtifact, body.openapi_artifact_id)
        if not oart or oart.environment_id != profile.environment_id:
            raise HTTPException(status.HTTP_404_NOT_FOUND, "OpenAPI artifact not found")
        opts["openapi_artifact_id"] = str(oart.id)
    if body.routes_artifact_id:
        rart = await session.get(RoutesArtifact, body.routes_artifact_id)
        if not rart or rart.environment_id != profile.environment_id:
            raise HTTPException(status.HTTP_404_NOT_FOUND, "Routes artifact not found")
        opts["routes_artifact_id"] = str(rart.id)
    if body.approval_id:
        ap = await session.get(Approval, body.approval_id)
        if not ap or ap.profile_id != profile.id:
            raise HTTPException(status.HTTP_404_NOT_FOUND, "Approval not found")
        opts["approval_id"] = str(ap.id)
    if body.note:
        opts["note"] = body.note

    run = ScanRun(
        profile_id=body.profile_id,
        started_by=actor_uuid(user),
        status="queued",
        trace_id=trace_id,
        baseline_scan_id=body.baseline_scan_id,
        idempotency_key=body.idempotency_key,
        options=opts,
    )
    session.add(run)
    await write_audit(
        session,
        actor_id=actor_uuid(user),
        action="scan.create",
        object_type="scan_run",
        object_id=str(run.id),
        payload={"profile_id": str(body.profile_id)},
    )
    await session.commit()
    await session.refresh(run)
    if (settings.redis_url or "").strip():
        if not await enqueue_scan(settings.redis_url, run.id):
            background_tasks.add_task(execute_scan, run.id)
    else:
        background_tasks.add_task(execute_scan, run.id)
    return run


@router.get("/{scan_id}", response_model=ScanOut)
async def get_scan(
    scan_id: uuid.UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    run = await session.get(ScanRun, scan_id)
    if not run:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    profile = await session.get(ScanProfile, run.profile_id)
    env = await session.get(Environment, profile.environment_id)
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    return run


@router.get("/{scan_id}/findings", response_model=list[FindingOut])
async def list_findings(
    scan_id: uuid.UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    run = await session.get(ScanRun, scan_id)
    if not run:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    profile = await session.get(ScanProfile, run.profile_id)
    env = await session.get(Environment, profile.environment_id)
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    res = await session.execute(select(Finding).where(Finding.scan_id == scan_id))
    return [FindingOut.from_orm_finding(f) for f in res.scalars().all()]


@router.get("/{scan_id}/events")
async def list_events(
    scan_id: uuid.UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    from cyber_db.models import ScanEvent

    run = await session.get(ScanRun, scan_id)
    if not run:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    profile = await session.get(ScanProfile, run.profile_id)
    env = await session.get(Environment, profile.environment_id)
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    res = await session.execute(select(ScanEvent).where(ScanEvent.scan_id == scan_id))
    rows = res.scalars().all()
    return [
        {
            "id": r.id,
            "ts": r.ts.isoformat(),
            "level": r.level,
            "adapter": r.adapter,
            "event_type": r.event_type,
            "message": r.message,
            "context": r.context,
        }
        for r in rows
    ]


@router.get("/{scan_id}/compare/{other_id}", response_model=CompareOut)
async def compare_scans(
    scan_id: uuid.UUID,
    other_id: uuid.UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    async def _auth_scan(sid: uuid.UUID) -> None:
        run = await session.get(ScanRun, sid)
        if not run:
            raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
        profile = await session.get(ScanProfile, run.profile_id)
        env = await session.get(Environment, profile.environment_id)
        proj = await session.get(Project, env.project_id)
        if not proj or proj.org_id != user.org_id:
            raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")

    await _auth_scan(scan_id)
    await _auth_scan(other_id)

    a = await session.execute(select(Finding.fingerprint).where(Finding.scan_id == scan_id))
    b = await session.execute(select(Finding.fingerprint).where(Finding.scan_id == other_id))
    set_a = set(a.scalars().all())
    set_b = set(b.scalars().all())
    return CompareOut(
        scan_id_a=scan_id,
        scan_id_b=other_id,
        new_fingerprints=sorted(set_b - set_a),
        resolved_fingerprints=sorted(set_a - set_b),
        unchanged_count=len(set_a & set_b),
    )
