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


def main() -> None:
    mcp.run()


if __name__ == "__main__":
    main()
