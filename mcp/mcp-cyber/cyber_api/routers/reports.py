from __future__ import annotations

import json
import uuid
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Response, status
from sqlalchemy import select

from cyber_api.deps import DbSession
from cyber_api.security import TokenUser, get_current_user
from cyber_db.models import Environment, Finding, Project, ScanProfile, ScanRun
from cyber_reports.checklist import build_remediation_checklist
from cyber_reports.markdown import render_markdown_report
from cyber_reports.sarif import build_sarif

router = APIRouter(prefix="/v1/reports", tags=["reports"])


async def _auth_scan(session: DbSession, user: TokenUser, scan_id: uuid.UUID) -> ScanRun:
    run = await session.get(ScanRun, scan_id)
    if not run:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    profile = await session.get(ScanProfile, run.profile_id)
    env = await session.get(Environment, profile.environment_id)
    proj = await session.get(Project, env.project_id)
    if not proj or proj.org_id != user.org_id:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    return run


@router.get("/{scan_id}.{fmt}")
async def export_report(
    scan_id: uuid.UUID,
    fmt: str,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    run = await _auth_scan(session, user, scan_id)
    profile = await session.get(ScanProfile, run.profile_id)
    env = await session.get(Environment, profile.environment_id)
    proj = await session.get(Project, env.project_id)
    res = await session.execute(select(Finding).where(Finding.scan_id == scan_id))
    findings = list(res.scalars().all())
    fd = [
        {
            "rule_id": x.rule_id,
            "title": x.title,
            "severity": x.severity,
            "status": x.status,
            "url": x.url,
            "affected_asset": x.affected_asset,
            "fingerprint": x.fingerprint,
            "remediation": x.remediation,
            "confidence": float(x.confidence),
        }
        for x in findings
    ]

    if fmt == "json":
        payload = {
            "scan_id": str(scan_id),
            "project": proj.name,
            "environment": env.name,
            "summary": run.summary,
            "findings": fd,
        }
        return Response(content=json.dumps(payload, indent=2), media_type="application/json")

    if fmt == "md":
        md = render_markdown_report(
            project_name=proj.name,
            environment_name=env.name,
            scan_id=str(scan_id),
            summary=dict(run.summary or {}),
            findings=fd,
        )
        return Response(content=md, media_type="text/markdown")

    if fmt == "sarif":
        sarif = build_sarif(scan_id=str(scan_id), findings=fd)
        return Response(content=json.dumps(sarif, indent=2), media_type="application/json")

    if fmt == "checklist":
        cl = build_remediation_checklist(fd)
        return Response(content=json.dumps(cl, indent=2), media_type="application/json")

    raise HTTPException(status.HTTP_400_BAD_REQUEST, "fmt must be json, md, sarif, or checklist")


@router.get("/posture/summary")
async def posture_summary(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    project_id: uuid.UUID | None = None,
    environment_id: uuid.UUID | None = None,
):
    """Aggregated posture for manager-style views."""
    q = select(Finding).join(Project, Finding.project_id == Project.id).where(Project.org_id == user.org_id)
    if project_id:
        q = q.where(Finding.project_id == project_id)
    if environment_id:
        q = q.where(Finding.environment_id == environment_id)
    res = await session.execute(q)
    rows = res.scalars().all()
    proj = None
    if project_id:
        proj = await session.get(Project, project_id)
        if proj and proj.org_id != user.org_id:
            raise HTTPException(status.HTTP_404_NOT_FOUND, "Project not found")
    open_rows = [r for r in rows if r.status == "open"]
    by_sev: dict[str, int] = {}
    for r in open_rows:
        by_sev[r.severity] = by_sev.get(r.severity, 0) + 1
    top = sorted(open_rows, key=lambda x: {"critical": 0, "high": 1, "medium": 2, "low": 3}.get(x.severity, 4))[:5]
    return {
        "open_count": len(open_rows),
        "by_severity": by_sev,
        "top_risks": [{"title": t.title, "severity": t.severity, "rule_id": t.rule_id} for t in top],
    }
