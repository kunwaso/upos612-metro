"""
MCP server for mcp-cyber: orchestrates scans and reads findings via the control-plane API only.
Does not perform network scans itself and does not modify application code (no autofix).
"""

from __future__ import annotations

import json
import os

import httpx
from mcp.server.fastmcp import FastMCP

from cyber_mcp.client import CyberApiClient

mcp = FastMCP("mcp-cyber")


def _client() -> CyberApiClient:
    return CyberApiClient()


@mcp.tool()
async def run_passive_scan(profile_id: str, target_urls: str | None = None) -> str:
    """Start a passive scan. target_urls: optional JSON array string of URLs."""
    body: dict = {"profile_id": profile_id}
    if target_urls:
        body["target_urls"] = json.loads(target_urls)
    out = await _client().post("/v1/scans", json=body)
    return json.dumps(out, indent=2)


@mcp.tool()
async def run_authenticated_scan(profile_id: str, target_urls: str | None = None) -> str:
    """Start a scan whose profile uses mode authenticated_passive (vault credential_ref, optional Playwright). Same API as run_passive_scan."""
    body: dict = {"profile_id": profile_id}
    if target_urls:
        body["target_urls"] = json.loads(target_urls)
    out = await _client().post("/v1/scans", json=body)
    return json.dumps(out, indent=2)


@mcp.tool()
async def list_findings(scan_id: str) -> str:
    """List findings for a completed scan run."""
    out = await _client().get(f"/v1/scans/{scan_id}/findings")
    return json.dumps(out, indent=2)


@mcp.tool()
async def get_finding_detail(finding_id: str) -> str:
    """Return one finding with evidence and remediation fields."""
    out = await _client().get(f"/v1/findings/{finding_id}")
    return json.dumps(out, indent=2)


@mcp.tool()
async def export_report(scan_id: str, fmt: str = "md") -> str:
    """Export report: fmt one of json, md, sarif, checklist."""
    base = os.environ.get("CYBER_API_URL", "http://127.0.0.1:8000").rstrip("/")
    token = os.environ.get("CYBER_API_TOKEN", "")
    headers = {"Authorization": f"Bearer {token}"} if token else {}
    async with httpx.AsyncClient(timeout=120.0) as client:
        r = await client.get(f"{base}/v1/reports/{scan_id}.{fmt}", headers=headers)
        r.raise_for_status()
        return r.text


@mcp.tool()
async def summarize_security_posture(project_id: str | None = None) -> str:
    """Executive-style summary of open findings (optionally scoped by project_id)."""
    q = "/v1/reports/posture/summary"
    if project_id:
        q += f"?project_id={project_id}"
    out = await _client().get(q)
    return json.dumps(out, indent=2)


@mcp.tool()
async def compare_scan_runs(scan_id_a: str, scan_id_b: str) -> str:
    """Diff fingerprints between two runs (new vs resolved)."""
    out = await _client().get(f"/v1/scans/{scan_id_a}/compare/{scan_id_b}")
    return json.dumps(out, indent=2)


@mcp.tool()
async def run_controlled_active_scan(
    profile_id: str,
    approval_id: str,
    target_urls: str | None = None,
) -> str:
    """Start active_controlled scan; profile must list adapter active_controlled_stub; approval_id must be approved."""
    body: dict = {"profile_id": profile_id, "approval_id": approval_id}
    if target_urls:
        body["target_urls"] = json.loads(target_urls)
    out = await _client().post("/v1/scans", json=body)
    return json.dumps(out, indent=2)


@mcp.tool()
async def request_scan_approval(
    profile_id: str,
    reason: str | None = None,
    expires_in_hours: int = 72,
) -> str:
    """Request approval for an active_controlled profile (developer+)."""
    body: dict = {
        "profile_id": profile_id,
        "expires_in_hours": expires_in_hours,
    }
    if reason:
        body["reason"] = reason
    out = await _client().post("/v1/approvals", json=body)
    return json.dumps(out, indent=2)


@mcp.tool()
async def list_approvals(status_filter: str | None = None, limit: int = 50) -> str:
    """List approvals for your org (manager+). status_filter: pending, approved, or rejected."""
    params: dict = {"limit": limit}
    if status_filter:
        params["status"] = status_filter
    out = await _client().get("/v1/approvals", params=params)
    return json.dumps(out, indent=2)


@mcp.tool()
async def approve_scan_approval(approval_id: str, note: str | None = None) -> str:
    """Approve a pending request (security_engineer or admin)."""
    payload = {"note": note} if note else {}
    out = await _client().post(f"/v1/approvals/{approval_id}/approve", json=payload)
    return json.dumps(out, indent=2)


@mcp.tool()
async def reject_scan_approval(approval_id: str, note: str | None = None) -> str:
    """Reject a pending approval request."""
    payload = {"note": note} if note else {}
    out = await _client().post(f"/v1/approvals/{approval_id}/reject", json=payload)
    return json.dumps(out, indent=2)


@mcp.tool()
async def get_audit_log(limit: int = 100) -> str:
    """Read audit log entries (security_engineer or admin)."""
    out = await _client().get("/v1/audit-log", params={"limit": limit})
    return json.dumps(out, indent=2)


@mcp.tool()
async def mark_finding_status(finding_id: str, status: str, reason: str | None = None) -> str:
    """Transition finding workflow status (open, in_progress, fixed, accepted_risk, suppressed, regressed)."""
    body: dict = {"status": status}
    if reason:
        body["reason"] = reason
    out = await _client().post(f"/v1/findings/{finding_id}/transition", json=body)
    return json.dumps(out, indent=2)


@mcp.tool()
async def create_suppression(
    project_id: str,
    fingerprint: str,
    reason: str,
    expires_at_iso: str | None = None,
) -> str:
    """Suppress a finding fingerprint for CI/triage (security_engineer or admin). expires_at_iso optional ISO-8601."""
    body: dict = {"project_id": project_id, "fingerprint": fingerprint, "reason": reason}
    if expires_at_iso:
        body["expires_at"] = expires_at_iso
    out = await _client().post("/v1/suppressions", json=body)
    return json.dumps(out, indent=2)


@mcp.tool()
async def list_suppressions(project_id: str, limit: int = 100) -> str:
    """List suppressions for a project."""
    out = await _client().get("/v1/suppressions", params={"project_id": project_id, "limit": limit})
    return json.dumps(out, indent=2)


@mcp.tool()
async def delete_suppression(suppression_id: str) -> str:
    """Remove a suppression rule."""
    await _client().delete(f"/v1/suppressions/{suppression_id}")
    return json.dumps({"deleted": suppression_id})


@mcp.tool()
async def get_fleet_posture() -> str:
    """Phase 4: per-project open finding counts by severity for your org."""
    out = await _client().get("/v1/analytics/fleet")
    return json.dumps(out, indent=2)


@mcp.tool()
async def get_finding_trends(days: int = 30) -> str:
    """Phase 4: daily new-finding volume by severity (first_seen_at buckets)."""
    out = await _client().get("/v1/analytics/trends/findings", params={"days": days})
    return json.dumps(out, indent=2)


@mcp.tool()
async def get_scan_volume_trends(days: int = 30) -> str:
    """Phase 4: completed successful scans per day."""
    out = await _client().get("/v1/analytics/trends/scans", params={"days": days})
    return json.dumps(out, indent=2)


@mcp.tool()
async def get_sla_breach_summary(max_age_days: int | None = None) -> str:
    """Phase 4: open critical/high findings older than SLA window (manager+)."""
    params: dict = {}
    if max_age_days is not None:
        params["max_age_days"] = max_age_days
    out = await _client().get("/v1/analytics/sla/open-high-critical", params=params)
    return json.dumps(out, indent=2)


@mcp.tool()
async def get_top_open_rules(limit: int = 20) -> str:
    """Phase 4: most common open findings by rule_id."""
    out = await _client().get("/v1/analytics/top-rules", params={"limit": limit})
    return json.dumps(out, indent=2)


def main() -> None:
    mcp.run()


if __name__ == "__main__":
    main()
