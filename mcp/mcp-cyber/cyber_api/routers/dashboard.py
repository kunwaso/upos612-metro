"""Dev dashboard: scans, events, audit, optional run-scan when enabled."""

from __future__ import annotations

import json
import uuid
from pathlib import Path
from typing import Any

from fastapi import APIRouter, BackgroundTasks, HTTPException, Response, status
from fastapi.responses import HTMLResponse, RedirectResponse
from pydantic import BaseModel
from sqlalchemy import delete, func, select, update
from sqlalchemy.orm import joinedload

from cyber_api.audit import write_audit
from cyber_api.deps import DbSession
from cyber_api.schemas import CompareOut
from cyber_api.settings import settings
from cyber_db.models import AuditLog, Environment, Finding, Project, ScanEvent, ScanProfile, ScanRun
from cyber_reports.markdown import render_markdown_report
from cyber_worker.redis_jobs import enqueue_scan
from cyber_worker.tasks import execute_scan

router = APIRouter(tags=["dashboard"])

_DASH_PATH = Path(__file__).resolve().parent.parent / "static" / "dashboard.html"
_DASH_HTML = (
    _DASH_PATH.read_text(encoding="utf-8")
    if _DASH_PATH.is_file()
    else "<html><body>Missing cyber_api/static/dashboard.html</body></html>"
)


class DashboardRunScan(BaseModel):
    profile_id: uuid.UUID
    target_urls: list[str] | None = None


class DashboardClearScanHistory(BaseModel):
    """POST body must be {\"confirm\": \"CLEAR\"} to delete all scan history."""

    confirm: str


def _severity_rank(sev: str) -> int:
    return {"critical": 0, "high": 1, "medium": 2, "low": 3, "info": 4}.get(sev.lower(), 5)


def _remediation_snip(rem: Any) -> str:
    if not rem:
        return ""
    if isinstance(rem, dict):
        parts: list[str] = []
        if rem.get("summary"):
            parts.append(str(rem["summary"]))
        steps = rem.get("steps") or []
        if isinstance(steps, list) and steps:
            parts.append("Steps:\n" + "\n".join(f"  - {x}" for x in steps[:15]))
        return "\n".join(parts)
    return str(rem)


def _refs_snip(refs: Any) -> str:
    if not refs or not isinstance(refs, list):
        return ""
    lines: list[str] = []
    for r in refs[:6]:
        if isinstance(r, dict):
            label = r.get("label") or "link"
            url = r.get("url") or ""
            lines.append(f"  - {label}: {url}")
        else:
            lines.append(f"  - {r}")
    return "\n".join(lines)


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
    scan_ids = [r.id for r in runs]

    counts_by_scan: dict[uuid.UUID, dict[str, int]] = {}
    preview_by_scan: dict[uuid.UUID, list[dict[str, Any]]] = {}
    if scan_ids:
        cnt_res = await session.execute(
            select(Finding.scan_id, Finding.severity, func.count())
            .where(Finding.scan_id.in_(scan_ids))
            .group_by(Finding.scan_id, Finding.severity)
        )
        for sid, severity, n in cnt_res.all():
            counts_by_scan.setdefault(sid, {})[str(severity).lower()] = int(n)

        prev_res = await session.execute(
            select(
                Finding.id,
                Finding.scan_id,
                Finding.title,
                Finding.severity,
                Finding.rule_id,
                Finding.url,
                Finding.component,
                Finding.parameter,
                Finding.fingerprint,
                Finding.reproduction,
                Finding.root_cause,
                Finding.remediation,
                Finding.external_refs,
            ).where(Finding.scan_id.in_(scan_ids))
        )
        tmp: dict[uuid.UUID, list[dict[str, Any]]] = {}
        for row in prev_res.all():
            (
                fid,
                sid,
                title,
                severity,
                rule_id,
                url,
                component,
                parameter,
                fingerprint,
                reproduction,
                root_cause,
                remediation,
                external_refs,
            ) = row
            tmp.setdefault(sid, []).append(
                {
                    "finding_id": str(fid),
                    "title": title,
                    "severity": str(severity).lower(),
                    "rule_id": rule_id,
                    "url": url,
                    "component": component,
                    "parameter": parameter,
                    "fingerprint": fingerprint,
                    "reproduction": reproduction,
                    "root_cause": root_cause,
                    "remediation_text": _remediation_snip(remediation),
                    "references_text": _refs_snip(external_refs),
                }
            )
        for sid, items in tmp.items():
            items.sort(key=lambda x: _severity_rank(x["severity"]))
            preview_by_scan[sid] = items[:8]

    scans_out: list[dict[str, Any]] = []
    for run in runs:
        prof = run.profile
        env = prof.environment if prof else None
        proj = env.project if env else None
        opts = dict(run.options or {})
        targets = opts.get("target_urls") or []
        if not isinstance(targets, list):
            targets = [str(targets)]
        summary = dict(run.summary or {})
        by_sev_raw = summary.get("by_severity") or {}
        by_sev = (
            {str(k).lower(): v for k, v in by_sev_raw.items()} if isinstance(by_sev_raw, dict) else {}
        )
        db_counts = counts_by_scan.get(run.id, {})
        known = ("critical", "high", "medium", "low", "info")
        merged_counts: dict[str, int] = {k: 0 for k in known}
        for key, n in db_counts.items():
            k = str(key).lower()
            add = int(n)
            if k in merged_counts:
                merged_counts[k] += add
            else:
                merged_counts["info"] += add
        for k in known:
            if not merged_counts[k] and by_sev:
                raw = by_sev.get(k, 0)
                try:
                    merged_counts[k] = max(merged_counts[k], int(raw))
                except (TypeError, ValueError):
                    pass
        db_total = sum(db_counts.values())
        try:
            summary_total = int(summary.get("total_findings") or 0)
        except (TypeError, ValueError):
            summary_total = 0
        total_findings = max(summary_total, db_total, sum(merged_counts.values()))
        scans_out.append(
            {
                "id": str(run.id),
                "status": run.status,
                "started_at": run.started_at.isoformat() if run.started_at else None,
                "finished_at": run.finished_at.isoformat() if run.finished_at else None,
                "trace_id": run.trace_id,
                "summary": run.summary,
                "severity_counts": merged_counts,
                "total_findings": total_findings,
                "findings_preview": preview_by_scan.get(run.id, []),
                "project_slug": proj.slug if proj else None,
                "environment_name": env.name if env else None,
                "base_url": env.base_url if env else None,
                "profile_name": prof.name if prof else None,
                "profile_id": str(prof.id) if prof else None,
                "profile_mode": prof.mode if prof else None,
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


@router.get("/dashboard/api/compare/{scan_id_a}/{scan_id_b}", include_in_schema=False)
async def dashboard_compare_scans(
    scan_id_a: uuid.UUID,
    scan_id_b: uuid.UUID,
    session: DbSession,
) -> CompareOut:
    """Fingerprint diff between two scans — same logic as GET /v1/scans/.../compare/... (dashboard-gated, no JWT)."""
    if not settings.dashboard_enabled:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Dashboard disabled")
    for sid in (scan_id_a, scan_id_b):
        run = await session.get(ScanRun, sid)
        if not run:
            raise HTTPException(status.HTTP_404_NOT_FOUND, "Scan not found")
    res_a = await session.execute(select(Finding.fingerprint).where(Finding.scan_id == scan_id_a))
    res_b = await session.execute(select(Finding.fingerprint).where(Finding.scan_id == scan_id_b))
    set_a = set(res_a.scalars().all())
    set_b = set(res_b.scalars().all())
    return CompareOut(
        scan_id_a=scan_id_a,
        scan_id_b=scan_id_b,
        new_fingerprints=sorted(set_b - set_a),
        resolved_fingerprints=sorted(set_a - set_b),
        unchanged_count=len(set_a & set_b),
    )


@router.get("/dashboard/api/meta", include_in_schema=False)
async def dashboard_meta(session: DbSession) -> dict[str, Any]:
    """Scan profiles for the test console (dashboard-gated); includes Phase 2 fields for hints."""
    if not settings.dashboard_enabled:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Dashboard disabled")
    stmt = (
        select(ScanProfile, Environment, Project)
        .join(Environment, ScanProfile.environment_id == Environment.id)
        .join(Project, Environment.project_id == Project.id)
        .order_by(Project.slug, Environment.name, ScanProfile.name)
    )
    res = await session.execute(stmt)
    rows = res.all()
    profiles: list[dict[str, Any]] = []
    for sp, env, proj in rows:
        p_opts = dict(sp.options or {})
        profiles.append(
            {
                "profile_id": str(sp.id),
                "profile_name": sp.name,
                "mode": sp.mode,
                "environment_id": str(env.id),
                "environment_name": env.name,
                "base_url": env.base_url,
                "project_slug": proj.slug,
                "project_name": proj.name,
                "adapter_ids": list(sp.adapter_ids or []),
                "credential_ref": sp.credential_ref or "",
                "playwright_login_url_set": bool(str(p_opts.get("playwright_login_url") or "").strip()),
            }
        )
    return {"profiles": profiles}


@router.post("/dashboard/api/run-scan", include_in_schema=False, response_model=None)
async def dashboard_run_scan(
    body: DashboardRunScan,
    session: DbSession,
    background_tasks: BackgroundTasks,
) -> dict[str, str]:
    """Queue a scan like POST /v1/scans but without JWT (only when dashboard is enabled)."""
    if not settings.dashboard_enabled:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Dashboard disabled")
    profile = await session.get(ScanProfile, body.profile_id)
    if not profile:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Profile not found")
    trace_id = str(uuid.uuid4())
    opts: dict[str, Any] = {}
    if body.target_urls:
        urls = [u.strip() for u in body.target_urls if u and str(u).strip()]
        if len(urls) > 50:
            raise HTTPException(status.HTTP_400_BAD_REQUEST, "Too many target_urls (max 50)")
        opts["target_urls"] = urls
    run = ScanRun(
        profile_id=body.profile_id,
        started_by=None,
        status="queued",
        trace_id=trace_id,
        options=opts,
    )
    session.add(run)
    await write_audit(
        session,
        actor_id=None,
        action="scan.create",
        object_type="scan_run",
        object_id=str(run.id),
        payload={"source": "dashboard", "profile_id": str(body.profile_id)},
    )
    await session.commit()
    await session.refresh(run)
    if (settings.redis_url or "").strip():
        if not await enqueue_scan(settings.redis_url, run.id):
            background_tasks.add_task(execute_scan, run.id)
    else:
        background_tasks.add_task(execute_scan, run.id)
    return {"id": str(run.id), "status": run.status, "trace_id": run.trace_id}


@router.post("/dashboard/api/clear-scan-history", include_in_schema=False, response_model=None)
async def dashboard_clear_scan_history(
    body: DashboardClearScanHistory,
    session: DbSession,
) -> dict[str, int]:
    """Remove all scan runs, findings, events, and scan/finding audit rows (dashboard-gated)."""
    if not settings.dashboard_enabled:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Dashboard disabled")
    if (body.confirm or "").strip() != "CLEAR":
        raise HTTPException(
            status.HTTP_400_BAD_REQUEST,
            'Body must be {"confirm": "CLEAR"}.',
        )

    n_findings = int(await session.scalar(select(func.count()).select_from(Finding)) or 0)
    n_events = int(await session.scalar(select(func.count()).select_from(ScanEvent)) or 0)
    n_runs = int(await session.scalar(select(func.count()).select_from(ScanRun)) or 0)
    n_audit = int(
        await session.scalar(
            select(func.count()).select_from(AuditLog).where(AuditLog.object_type.in_(["scan_run", "finding"]))
        )
        or 0
    )

    await session.execute(update(ScanRun).values(baseline_scan_id=None))
    await session.execute(delete(Finding))
    await session.execute(delete(ScanEvent))
    await session.execute(delete(ScanRun))
    await session.execute(delete(AuditLog).where(AuditLog.object_type.in_(["scan_run", "finding"])))

    await write_audit(
        session,
        actor_id=None,
        action="dashboard.clear_scan_history",
        object_type="system",
        object_id="scan_history",
        payload={
            "deleted_runs": n_runs,
            "deleted_findings": n_findings,
            "deleted_events": n_events,
            "deleted_audit_rows": n_audit,
        },
    )
    await session.commit()

    return {
        "deleted_runs": n_runs,
        "deleted_findings": n_findings,
        "deleted_events": n_events,
        "deleted_audit_rows": n_audit,
    }


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
