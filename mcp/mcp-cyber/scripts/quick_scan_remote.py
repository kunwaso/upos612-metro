#!/usr/bin/env python3
"""
Register a remote (HTTPS) site as an Environment, create a passive ScanProfile, run a scan, poll until done.

Prerequisites:
  - mcp-cyber API running (e.g. python -m cyber_api)
  - CYBER_API_TOKEN = JWT from: python scripts/seed_demo.py
  - CYBER_API_URL in env or mcp/mcp-cyber/.env

Example:
  cd mcp/mcp-cyber
  python scripts/quick_scan_remote.py --base-url https://plm.pekofactory.store/

Only use against hosts you own or are authorized to test.
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import time
from pathlib import Path
from urllib.parse import urlparse

import httpx

ROOT = Path(__file__).resolve().parent.parent


def _load_env_file(path: Path) -> None:
    if not path.is_file():
        return
    for raw in path.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if not line or line.startswith("#"):
            continue
        if line.startswith("export "):
            line = line[7:].strip()
        if "=" not in line:
            continue
        key, _, val = line.partition("=")
        key = key.strip()
        val = val.strip().strip("'\"")
        if key and key not in os.environ:
            os.environ[key] = val


def _headers(token: str) -> dict[str, str]:
    return {
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json",
    }


def _target_dedupe_key(url: str) -> str:
    """Normalize URL for equality (scheme/host/path/query); ignores fragment."""
    p = urlparse(url.strip())
    scheme = (p.scheme or "https").lower()
    host = (p.hostname or "").lower()
    if not host:
        return url.strip().lower()
    port = ""
    if p.port and not ((scheme == "https" and p.port == 443) or (scheme == "http" and p.port == 80)):
        port = f":{p.port}"
    path = (p.path or "/").rstrip("/") or "/"
    q = f"?{p.query}" if p.query else ""
    return f"{scheme}://{host}{port}{path}{q}"


def _dedupe_target_urls(urls: list[str]) -> list[str]:
    seen: set[str] = set()
    out: list[str] = []
    for u in urls:
        key = _target_dedupe_key(u)
        if key in seen:
            continue
        seen.add(key)
        out.append(u.strip())
    return out


def main() -> int:
    _load_env_file(ROOT / ".env")

    p = argparse.ArgumentParser(description="Register remote URL and run passive mcp-cyber scan")
    p.add_argument(
        "--api-url",
        default=os.environ.get("CYBER_API_URL", "http://127.0.0.1:8686"),
        help="mcp-cyber FastAPI base URL",
    )
    p.add_argument(
        "--token",
        default=os.environ.get("CYBER_API_TOKEN", ""),
        help="JWT (or set CYBER_API_TOKEN)",
    )
    p.add_argument(
        "--base-url",
        required=True,
        help="Target site base URL, e.g. https://plm.pekofactory.store",
    )
    p.add_argument(
        "--project-slug",
        default="demo",
        help="Project slug (default: demo from seed_demo.py)",
    )
    p.add_argument(
        "--env-name",
        default="plm-remote",
        help="Environment name to create",
    )
    p.add_argument(
        "--env-class",
        default="prod",
        choices=["local", "dev", "staging", "uat", "prod"],
        help="Environment class (prod = passive-only in default policy)",
    )
    p.add_argument(
        "--profile-name",
        default="plm-passive",
        help="Scan profile name",
    )
    p.add_argument(
        "--target-urls",
        default="",
        help="Comma-separated extra URLs to scan (default: base-url + /login if you add paths)",
    )
    p.add_argument(
        "--poll-seconds",
        type=int,
        default=120,
        help="Max time to wait for scan to finish",
    )
    p.add_argument(
        "--register-only",
        action="store_true",
        help="Only create environment + profile; do not start scan",
    )
    args = p.parse_args()

    base = args.api_url.rstrip("/")
    token = args.token.strip()
    if not token:
        print("ERROR: Set CYBER_API_TOKEN or pass --token (JWT from scripts/seed_demo.py)", file=sys.stderr)
        return 1

    raw_base = args.base_url.strip().rstrip("/")
    parsed = urlparse(raw_base)
    if parsed.scheme not in ("https", "http"):
        print("ERROR: --base-url must start with http:// or https://", file=sys.stderr)
        return 1
    host = (parsed.hostname or "").lower()
    if not host:
        print("ERROR: could not parse hostname from --base-url", file=sys.stderr)
        return 1

    targets: list[str] = [raw_base + "/"]
    if args.target_urls.strip():
        for part in args.target_urls.split(","):
            u = part.strip()
            if u:
                targets.append(u)
    # light default: common entry points (safe GET)
    if len(targets) == 1:
        targets.append(raw_base + "/login")
    targets = _dedupe_target_urls(targets)

    client = httpx.Client(base_url=base, headers=_headers(token), timeout=60.0)

    # 1) Resolve project
    r = client.get("/v1/projects")
    r.raise_for_status()
    projects = r.json()
    proj = next((x for x in projects if x.get("slug") == args.project_slug), None)
    if not proj:
        print(f"ERROR: No project with slug={args.project_slug!r}. Run scripts/seed_demo.py or create a project.", file=sys.stderr)
        return 1
    project_id = proj["id"]
    print(f"Using project: {proj.get('name')} ({project_id})")

    # 2) List environments — reuse if same base_url
    r = client.get(f"/v1/projects/{project_id}/environments")
    r.raise_for_status()
    envs = r.json()
    env_row = next((e for e in envs if e.get("base_url") == raw_base), None)

    if env_row:
        environment_id = env_row["id"]
        print(f"Reusing environment: {env_row.get('name')} ({environment_id})")
    else:
        body = {
            "name": args.env_name,
            "env_class": args.env_class,
            "base_url": raw_base,
            "allowlist": {
                "hosts": [host],
                "path_prefixes": ["/"],
            },
        }
        r = client.post(f"/v1/projects/{project_id}/environments", json=body)
        if r.status_code == 409:
            print("ERROR: conflict creating environment (name taken?). Choose --env-name.", file=sys.stderr)
            print(r.text, file=sys.stderr)
            return 1
        r.raise_for_status()
        environment_id = r.json()["id"]
        print(f"Created environment: {args.env_name} ({environment_id})")

    # 3) Scan profile — reuse if name exists for this env
    r = client.get(f"/v1/environments/{environment_id}/scan-profiles")
    r.raise_for_status()
    profiles = r.json()
    prof = next((x for x in profiles if x.get("name") == args.profile_name), None)
    if prof:
        profile_id = prof["id"]
        print(f"Reusing scan profile: {args.profile_name} ({profile_id})")
    else:
        body = {
            "name": args.profile_name,
            "mode": "passive",
            "adapter_ids": ["headers_cookies", "openapi_lint"],
            "rate_limit_rps": 1.0,
            "max_concurrency": 2,
            "options": {},
        }
        r = client.post(f"/v1/environments/{environment_id}/scan-profiles", json=body)
        r.raise_for_status()
        profile_id = r.json()["id"]
        print(f"Created scan profile: {args.profile_name} ({profile_id})")

    if args.register_only:
        print("\nRegister-only: done. Start a scan with MCP run_passive_scan or POST /v1/scans with:")
        print(json.dumps({"profile_id": profile_id, "target_urls": targets}, indent=2))
        return 0

    # 4) Start scan
    r = client.post(
        "/v1/scans",
        json={"profile_id": profile_id, "target_urls": targets},
    )
    r.raise_for_status()
    scan = r.json()
    scan_id = scan["id"]
    trace_id = scan.get("trace_id", "")
    print(f"Started scan: {scan_id} (trace_id={trace_id})")

    # 5) Poll
    deadline = time.monotonic() + args.poll_seconds
    while time.monotonic() < deadline:
        r = client.get(f"/v1/scans/{scan_id}")
        r.raise_for_status()
        row = r.json()
        status = row.get("status")
        print(f"  status={status}")
        if status in ("succeeded", "failed", "cancelled"):
            break
        time.sleep(2)
    else:
        print("ERROR: timeout waiting for scan", file=sys.stderr)
        return 1

    if row.get("status") != "succeeded":
        print("Scan did not succeed:", json.dumps(row.get("summary") or row, indent=2))
        return 1

    r = client.get(f"/v1/scans/{scan_id}/findings")
    r.raise_for_status()
    findings = r.json()
    print(f"\nFindings: {len(findings)}")
    print(f"Markdown report: {base}/v1/reports/{scan_id}.md")
    print(f"JSON report:       {base}/v1/reports/{scan_id}.json")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
