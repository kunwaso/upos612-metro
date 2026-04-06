"""Phase 4 analytics: fleet posture, trends, SLA-style breaches, top rules, scan volume."""

from __future__ import annotations

import uuid
from datetime import datetime, timedelta, timezone
from typing import Annotated, Any

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import func, literal_column, select

from cyber_api.deps import DbSession
from cyber_api.security import TokenUser, get_current_user
from cyber_api.settings import settings
from cyber_db.models import Environment, Finding, Project, ScanProfile, ScanRun
from cyber_reports.analytics_format import fold_fleet_rows, fold_trend_rows

router = APIRouter(prefix="/v1/analytics", tags=["analytics"])


def _day_fmt() -> Any:
    return literal_column("'YYYY-MM-DD'")


@router.get("/fleet")
async def fleet_posture(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    """Per-project open finding counts by severity (org-wide fleet view)."""
    stmt = (
        select(
            Finding.project_id,
            Project.slug,
            Project.name,
            Finding.severity,
            func.count().label("cnt"),
        )
        .join(Project, Finding.project_id == Project.id)
        .where(Project.org_id == user.org_id, Finding.status == "open")
        .group_by(Finding.project_id, Project.slug, Project.name, Finding.severity)
        .order_by(Project.slug)
    )
    res = await session.execute(stmt)
    raw = [(r[0], r[1], r[2], r[3], int(r[4])) for r in res.all()]
    return {"projects": fold_fleet_rows(raw)}


@router.get("/trends/findings")
async def finding_trends(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    days: int = Query(30, ge=1, le=366),
):
    """New finding volume by calendar day (PostgreSQL to_char bucket on first_seen_at)."""
    max_days = min(days, settings.analytics_max_trend_days)
    since = datetime.now(timezone.utc) - timedelta(days=max_days)
    day_col = func.to_char(Finding.first_seen_at, _day_fmt()).label("day")
    stmt = (
        select(day_col, Finding.severity, func.count().label("cnt"))
        .join(Project, Finding.project_id == Project.id)
        .where(Project.org_id == user.org_id, Finding.first_seen_at >= since)
        .group_by(day_col, Finding.severity)
        .order_by(day_col)
    )
    res = await session.execute(stmt)
    raw = [(str(r[0]), str(r[1]), int(r[2])) for r in res.all()]
    return {"days": max_days, "series": fold_trend_rows(raw)}


@router.get("/sla/open-high-critical")
async def sla_open_high_critical(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    max_age_days: int | None = Query(None, ge=1, le=365, description="Override default SLA window"),
):
    """
    Count (and sample) open critical/high findings older than SLA window (default from CYBER_SLA_HIGH_CRITICAL_DAYS).
    """
    if user.role not in ("manager", "security_engineer", "admin"):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Manager, security_engineer, or admin required")
    window = max_age_days if max_age_days is not None else settings.sla_high_critical_days
    cutoff = datetime.now(timezone.utc) - timedelta(days=window)
    base = (
        select(func.count())
        .select_from(Finding)
        .join(Project, Finding.project_id == Project.id)
        .where(
            Project.org_id == user.org_id,
            Finding.status == "open",
            Finding.severity.in_(("critical", "high")),
            Finding.first_seen_at < cutoff,
        )
    )
    breach_count = int(await session.scalar(base) or 0)

    sample_stmt = (
        select(
            Finding.id,
            Finding.title,
            Finding.severity,
            Finding.first_seen_at,
            Finding.fingerprint,
            Project.slug,
        )
        .join(Project, Finding.project_id == Project.id)
        .where(
            Project.org_id == user.org_id,
            Finding.status == "open",
            Finding.severity.in_(("critical", "high")),
            Finding.first_seen_at < cutoff,
        )
        .order_by(Finding.first_seen_at)
        .limit(50)
    )
    samp = await session.execute(sample_stmt)
    samples = [
        {
            "finding_id": str(r[0]),
            "title": r[1],
            "severity": r[2],
            "first_seen_at": r[3].isoformat() if r[3] else None,
            "fingerprint": r[4],
            "project_slug": r[5],
        }
        for r in samp.all()
    ]
    return {
        "sla_window_days": window,
        "breach_count": breach_count,
        "sample": samples,
    }


@router.get("/top-rules")
async def top_rules(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    limit: int = Query(20, ge=1, le=100),
):
    """Most frequent open findings by rule_id (fleet-wide within org)."""
    stmt = (
        select(Finding.rule_id, func.count().label("cnt"))
        .join(Project, Finding.project_id == Project.id)
        .where(Project.org_id == user.org_id, Finding.status == "open")
        .group_by(Finding.rule_id)
        .order_by(func.count().desc())
        .limit(limit)
    )
    res = await session.execute(stmt)
    return {"rules": [{"rule_id": r[0], "open_count": int(r[1])} for r in res.all()]}


@router.get("/trends/scans")
async def scan_volume_trends(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    days: int = Query(30, ge=1, le=366),
):
    """Succeeded scan runs finished per day (throughput signal)."""
    max_days = min(days, settings.analytics_max_trend_days)
    since = datetime.now(timezone.utc) - timedelta(days=max_days)
    day_col = func.to_char(ScanRun.finished_at, _day_fmt()).label("day")
    stmt = (
        select(day_col, func.count().label("cnt"))
        .select_from(ScanRun)
        .join(ScanProfile, ScanRun.profile_id == ScanProfile.id)
        .join(Environment, ScanProfile.environment_id == Environment.id)
        .join(Project, Environment.project_id == Project.id)
        .where(
            Project.org_id == user.org_id,
            ScanRun.status == "succeeded",
            ScanRun.finished_at.isnot(None),
            ScanRun.finished_at >= since,
        )
        .group_by(day_col)
        .order_by(day_col)
    )
    res = await session.execute(stmt)
    series = [{"date": str(r[0]), "completed_scans": int(r[1])} for r in res.all()]
    return {"days": max_days, "series": series}
