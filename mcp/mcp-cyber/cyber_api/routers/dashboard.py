"""Read-only dev dashboard: recent scans, scan_events, audit tail."""

from __future__ import annotations

import json
import uuid
from pathlib import Path
from typing import Any

from fastapi import APIRouter, HTTPException, Response, status
from fastapi.responses import HTMLResponse, RedirectResponse
from sqlalchemy import select
from sqlalchemy.orm import joinedload

from cyber_api.deps import DbSession
from cyber_api.settings import settings
from cyber_db.models import AuditLog, Environment, Finding, Project, ScanEvent, ScanProfile, ScanRun
from cyber_reports.markdown import render_markdown_report

router = APIRouter(tags=["dashboard"])

_DASH_PATH = Path(__file__).resolve().parent.parent / "static" / "dashboard.html"
_DASH_HTML = (
    _DASH_PATH.read_text(encoding="utf-8")
    if _DASH_PATH.is_file()
    else "<html><body>Missing cyber_api/static/dashboard.html</body></html>"
)


def _redact_payload(payload: dict[str, Any]) -> dict[str, Any]:
    out = dict(payload or {})
    for k in list(out.keys()):
        if "token" in k.lower() or "secret" in k.lower():
            out[k] = "[redacted]"
    return out


@router.get("/", response_class=HTMLResponse, include_in_schema=False, response_model=None)
async def dashboard_home() -> HTMLResponse | RedirectResponse:
    if not settings.dashboard_enabled:
        return RedirectResponse(url="/docs", status_code=302)
    return HTMLResponse(content=_DASH_HTML)


@router.get("/dashboard/api/feed", include_in_schema=False)
async def dashboard_feed(session: DbSession) -> dict[str, Any]:
    if not settings.dashboard_enabled:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Dashboard disabled")

    scan_stmt = (
        select(ScanRun)
        .options(joinedload(ScanRun.profile).joinedload(ScanProfile.environment).joinedload(Environment.project))
        .order_by(ScanRun.started_at.desc())
        .limit(50)
    )
    scan_res = await session.execute(scan_stmt)
    runs = scan_res.unique().scalars().all()

    scans_out: list[dict[str, Any]] = []
    for run in runs:
        prof = run.profile
        env = prof.environment if prof else None
        proj = env.project if env else None
        opts = dict(run.options or {})
        targets = opts.get("target_urls") or []
        if not isinstance(targets, list):
            targets = [str(targets)]
        scans_out.append(
            {
                "id": str(run.id),
                "status": run.status,
                "started_at": run.started_at.isoformat() if run.started_at else None,
                "finished_at": run.finished_at.isoformat() if run.finished_at else None,
                "trace_id": run.trace_id,
                "summary": run.summary,
                "project_slug": proj.slug if proj else None,
                "environment_name": env.name if env else None,
                "base_url": env.base_url if env else None,
                "profile_name": prof.name if prof else None,
                "target_urls": [str(u) for u in targets],
            }
        )

    ev_stmt = select(ScanEvent).order_by(ScanEvent.ts.desc()).limit(200)
    ev_res = await session.execute(ev_stmt)
    events_rows = ev_res.scalars().all()
    events_out = [
        {
            "id": r.id,
            "scan_id": str(r.scan_id),
            "ts": r.ts.isoformat() if r.ts else None,
            "level": r.level,
            "adapter": r.adapter,
            "event_type": r.event_type,
            "message": r.message,
            "context": r.context or {},
        }
        for r in events_rows
    ]

    au_stmt = select(AuditLog).order_by(AuditLog.ts.desc()).limit(80)
    au_res = await session.execute(au_stmt)
    audit_rows = au_res.scalars().all()
    audit_out = [
        {
            "id": r.id,
            "ts": r.ts.isoformat() if r.ts else None,
            "actor_id": str(r.actor_id) if r.actor_id else None,
            "action": r.action,
            "object_type": r.object_type,
            "object_id": r.object_id,
            "payload": _redact_payload(dict(r.payload or {})),
        }
        for r in audit_rows
    ]

    return {"scans": scans_out, "events": events_out, "audit": audit_out}


@router.get("/dashboard/reports/{scan_id}/{fmt}", include_in_schema=False)
async def dashboard_report_export(
    scan_id: uuid.UUID,
    fmt: str,
    session: DbSession,
) -> Response:
    """Same payloads as /v1/reports when dashboard is on (local dev; no JWT)."""
    if not settings.dashboard_enabled:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Dashboard disabled")
    if fmt not in ("md", "json"):
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "fmt must be md or json")

    run = await session.get(ScanRun, scan_id)
    if not run:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
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

    md = render_markdown_report(
        project_name=proj.name,
        environment_name=env.name,
        scan_id=str(scan_id),
        summary=dict(run.summary or {}),
        findings=fd,
    )
    return Response(content=md, media_type="text/markdown")
