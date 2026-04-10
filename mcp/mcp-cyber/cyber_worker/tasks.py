"""Execute scan runs (async); invoked from API BackgroundTasks or CLI."""

from __future__ import annotations

import traceback
import uuid
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import httpx
import structlog
from cyber_api.settings import settings
from sqlalchemy import func, select
from sqlalchemy.orm import selectinload

from cyber_db.models import Approval, Environment, Finding, OpenAPIArtifact, RoutesArtifact, ScanEvent, ScanProfile, ScanRun
from cyber_db.session import async_session_factory
from cyber_engine.adapters.base import ScanContext
from cyber_engine.credentials.vault import resolve_credential_ref
from cyber_engine.orchestrator import Orchestrator
from cyber_engine.policy_engine import PolicyEngine
from cyber_engine.rate_limit import AsyncRateLimiter
from cyber_worker.suppression_check import is_fingerprint_suppressed

log = structlog.get_logger()


async def execute_scan(scan_id: uuid.UUID) -> None:
    async with async_session_factory() as session:
        try:
            stmt = (
                select(ScanRun)
                .where(ScanRun.id == scan_id)
                .options(
                    selectinload(ScanRun.profile).selectinload(ScanProfile.environment).selectinload(
                        Environment.project
                    ),
                )
            )
            run = (await session.execute(stmt)).scalar_one_or_none()
            if not run:
                log.error("scan_not_found", scan_id=str(scan_id))
                return

            profile = run.profile
            env = profile.environment
            project = env.project
            opts = dict(run.options or {})

            run.status = "running"
            await session.commit()
            await session.refresh(run)

            approval_status: str | None = None
            aid = opts.get("approval_id")
            if aid:
                ap = await session.get(Approval, uuid.UUID(str(aid)))
                if ap:
                    if ap.profile_id != profile.id:
                        raise ValueError("approval_id does not belong to this scan profile")
                    approval_status = ap.status

            openapi_json: str | None = None
            oaid = opts.get("openapi_artifact_id")
            if oaid:
                art = await session.get(OpenAPIArtifact, uuid.UUID(str(oaid)))
                if art:
                    if art.environment_id != env.id:
                        raise ValueError("openapi_artifact_id does not belong to scan environment")
                    p = Path(art.storage_uri)
                    if p.is_file():
                        openapi_json = p.read_text(encoding="utf-8")

            routes_json: str | None = None
            raid = opts.get("routes_artifact_id")
            if raid:
                rart = await session.get(RoutesArtifact, uuid.UUID(str(raid)))
                if rart:
                    if rart.environment_id != env.id:
                        raise ValueError("routes_artifact_id does not belong to scan environment")
                    rp = Path(rart.storage_uri)
                    if rp.is_file():
                        routes_json = rp.read_text(encoding="utf-8")

            target_urls = opts.get("target_urls") or []

            profile_opts = dict(profile.options or {})
            merged_options: dict[str, Any] = {
                **profile_opts,
                "approval_id": str(aid) if aid else None,
                "approval_status": approval_status,
            }
            if openapi_json is not None:
                merged_options["openapi_json"] = openapi_json
            if routes_json is not None:
                merged_options["routes_json"] = routes_json
            merged_options.pop("resolved_credentials", None)
            skip_run_keys = {
                "target_urls",
                "openapi_artifact_id",
                "routes_artifact_id",
                "approval_id",
                "resolved_credentials",
            }
            for k, v in opts.items():
                if k in skip_run_keys:
                    continue
                merged_options[k] = v

            resolved = resolve_credential_ref(profile.credential_ref)
            if resolved:
                merged_options["resolved_credentials"] = resolved

            policy_extra: list[str] = []
            login_url = merged_options.get("playwright_login_url")
            if login_url:
                policy_extra.append(str(login_url).strip())

            orch = Orchestrator()
            limiter = AsyncRateLimiter(float(profile.rate_limit_rps or 2))
            async with httpx.AsyncClient(
                headers={"User-Agent": "mcp-cyber/0.1 (defensive scan; authorized)"},
                follow_redirects=True,
            ) as client:
                ctx = ScanContext(
                    scan_id=str(run.id),
                    trace_id=run.trace_id,
                    profile_id=str(profile.id),
                    mode=profile.mode,
                    adapter_ids=list(profile.adapter_ids or []),
                    rate_limit_rps=float(profile.rate_limit_rps or 2),
                    max_concurrency=int(profile.max_concurrency or 3),
                    environment_id=str(env.id),
                    environment_name=env.name,
                    environment_class=env.env_class,
                    base_url=env.base_url,
                    allowlist=dict(env.allowlist or {}),
                    project_id=str(project.id),
                    options=merged_options,
                    target_urls=list(target_urls) if target_urls else [],
                    policy_extra_urls=policy_extra,
                    business_rules_path=settings.business_rules_path,
                    http_client=client,
                    rate_limiter=limiter,
                )
                rows = await orch.run(ctx)

            # One row per (fingerprint): orchestrator may emit duplicates (e.g. same URL listed twice in target_urls).
            seen_fp: set[str] = set()
            deduped: list[dict[str, Any]] = []
            for row in rows:
                fp = row["fingerprint"]
                if fp in seen_fp:
                    continue
                seen_fp.add(fp)
                deduped.append(row)
            rows = deduped
            gate_inputs = [{**row, "status": "open"} for row in rows]
            ci_blocked = PolicyEngine().should_block_findings(gate_inputs, gate_name="ci_default")
            if ci_blocked:
                session.add(
                    ScanEvent(
                        scan_id=run.id,
                        level="warn",
                        adapter="policy",
                        event_type="policy_gate_block",
                        message="Findings match policy.gates.ci_default.block_if",
                        context={"gate": "ci_default"},
                    )
                )

            for row in rows:
                if await is_fingerprint_suppressed(session, row["project_id"], row["fingerprint"]):
                    continue
                session.add(
                    Finding(
                        id=uuid.uuid4(),
                        scan_id=run.id,
                        project_id=row["project_id"],
                        environment_id=row["environment_id"],
                        rule_id=row["rule_id"],
                        category=row["category"],
                        title=row["title"],
                        severity=row["severity"],
                        confidence=row["confidence"],
                        cvss_score=row.get("cvss_score"),
                        status="open",
                        affected_asset=row.get("affected_asset"),
                        url=row.get("url"),
                        component=row.get("component"),
                        parameter=row.get("parameter"),
                        fingerprint=row["fingerprint"],
                        evidence=row.get("evidence") or [],
                        reproduction=row.get("reproduction"),
                        root_cause=row.get("root_cause"),
                        remediation=row.get("remediation") or {},
                        external_refs=row.get("external_refs") or [],
                        tags=row.get("tags") or [],
                    )
                )
                session.add(
                    ScanEvent(
                        scan_id=run.id,
                        level="info",
                        adapter="normalizer",
                        event_type="finding_recorded",
                        message=row["title"][:500],
                        context={"rule_id": row["rule_id"], "fingerprint": row["fingerprint"]},
                    )
                )

            summary = await _scan_summary(session, run.id)
            summary["policy_ci_block"] = bool(ci_blocked)
            run.status = "succeeded"
            run.finished_at = datetime.now(timezone.utc)
            run.summary = summary
            session.add(
                ScanEvent(
                    scan_id=run.id,
                    level="info",
                    adapter="orchestrator",
                    event_type="scan_completed",
                    message="Scan finished",
                    context=summary,
                )
            )
            await session.commit()
            log.info("scan_done", scan_id=str(scan_id), findings=len(rows))
        except Exception as e:
            log.exception("scan_failed", scan_id=str(scan_id))
            await session.rollback()
            run2 = await session.get(ScanRun, scan_id)
            if run2:
                run2.status = "failed"
                run2.finished_at = datetime.now(timezone.utc)
                run2.summary = {"error": str(e), "traceback": traceback.format_exc()[-8000:]}
                session.add(
                    ScanEvent(
                        scan_id=scan_id,
                        level="error",
                        adapter="orchestrator",
                        event_type="scan_failed",
                        message=str(e),
                        context={"traceback": traceback.format_exc()[-4000:]},
                    )
                )
                await session.commit()


async def _scan_summary(session: Any, scan_id: uuid.UUID) -> dict[str, Any]:
    stmt = select(Finding.severity, func.count()).where(Finding.scan_id == scan_id).group_by(Finding.severity)
    res = await session.execute(stmt)
    by_sev = {row[0]: row[1] for row in res.all()}
    total = await session.scalar(select(func.count()).select_from(Finding).where(Finding.scan_id == scan_id))
    return {"total_findings": int(total or 0), "by_severity": by_sev, "open_count": int(total or 0)}
