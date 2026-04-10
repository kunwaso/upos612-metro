#!/usr/bin/env python3
"""
Apply a scan profile template and optionally run a controlled scan.

Usage examples:
  python scripts/apply_profile_template.py --base-url https://staging.example.com
  python scripts/apply_profile_template.py --base-url https://staging.example.com --approval-id <approved-uuid>

Only use against systems you own or are authorized to test.
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
DEFAULT_TEMPLATE = ROOT / "configs" / "profile_templates" / "enterprise_active_controlled_fuzz_approvals.v1.json"


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
    return {"Authorization": f"Bearer {token}", "Content-Type": "application/json"}


def _target_dedupe_key(url: str) -> str:
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


def _load_template(path: Path) -> tuple[dict, list[str], str]:
    if not path.is_file():
        raise FileNotFoundError(f"Template not found: {path}")
    doc = json.loads(path.read_text(encoding="utf-8"))
    profile = doc.get("scan_profile")
    if not isinstance(profile, dict):
        raise ValueError("Template must include a 'scan_profile' object")
    for key in ("name", "mode", "adapter_ids"):
        if key not in profile:
            raise ValueError(f"Template scan_profile missing required key: {key}")
    body = {
        "name": str(profile["name"]),
        "mode": str(profile["mode"]),
        "adapter_ids": [str(x) for x in profile["adapter_ids"]],
        "rate_limit_rps": float(profile.get("rate_limit_rps", 1.0)),
        "max_concurrency": int(profile.get("max_concurrency", 2)),
        "credential_ref": profile.get("credential_ref"),
        "options": dict(profile.get("options") or {}),
    }
    env_defaults = doc.get("environment_defaults") or {}
    path_prefixes = env_defaults.get("path_prefixes") or ["/"]
    path_prefixes = [str(p) for p in path_prefixes if str(p).strip()] or ["/"]
    template_id = str(doc.get("template_id") or path.stem)
    return body, path_prefixes, template_id


def _profile_matches(existing: dict, desired: dict) -> tuple[bool, list[str]]:
    drift: list[str] = []
    if str(existing.get("mode") or "") != str(desired.get("mode") or ""):
        drift.append("mode")
    if sorted(existing.get("adapter_ids") or []) != sorted(desired.get("adapter_ids") or []):
        drift.append("adapter_ids")
    if abs(float(existing.get("rate_limit_rps") or 0.0) - float(desired.get("rate_limit_rps") or 0.0)) > 1e-6:
        drift.append("rate_limit_rps")
    if int(existing.get("max_concurrency") or 0) != int(desired.get("max_concurrency") or 0):
        drift.append("max_concurrency")
    if (existing.get("credential_ref") or None) != (desired.get("credential_ref") or None):
        drift.append("credential_ref")
    if dict(existing.get("options") or {}) != dict(desired.get("options") or {}):
        drift.append("options")
    return len(drift) == 0, drift


def main() -> int:
    _load_env_file(ROOT / ".env")

    p = argparse.ArgumentParser(description="Apply a scan profile template for consistent team execution")
    p.add_argument("--api-url", default=os.environ.get("CYBER_API_URL", "http://127.0.0.1:8686"))
    p.add_argument("--token", default=os.environ.get("CYBER_API_TOKEN", ""))
    p.add_argument("--base-url", required=True, help="Target base URL, e.g. https://staging.example.com")
    p.add_argument("--project-slug", default="demo")
    p.add_argument("--env-name", default="enterprise-active-controlled")
    p.add_argument("--env-class", default="staging", choices=["local", "dev", "staging", "uat", "prod"])
    p.add_argument("--template", default=str(DEFAULT_TEMPLATE), help="Path to profile template JSON")
    p.add_argument("--profile-name", default="", help="Optional override for template profile name")
    p.add_argument(
        "--allow-existing-drift",
        action="store_true",
        help="Reuse existing profile even if it differs from template",
    )
    p.add_argument("--approval-id", default="", help="Approved approval_id to run scan immediately")
    p.add_argument("--target-urls", default="", help="Comma-separated target URLs (default: base-url only)")
    p.add_argument("--poll-seconds", type=int, default=180, help="Poll timeout when --approval-id is set")
    args = p.parse_args()

    token = args.token.strip()
    if not token:
        print("ERROR: Set CYBER_API_TOKEN or pass --token (JWT from scripts/seed_demo.py)", file=sys.stderr)
        return 1

    base_url = args.base_url.strip().rstrip("/")
    parsed = urlparse(base_url)
    if parsed.scheme not in ("https", "http"):
        print("ERROR: --base-url must start with http:// or https://", file=sys.stderr)
        return 1
    host = (parsed.hostname or "").lower()
    if not host:
        print("ERROR: could not parse hostname from --base-url", file=sys.stderr)
        return 1

    template_body, path_prefixes, template_id = _load_template(Path(args.template))
    if args.profile_name.strip():
        template_body["name"] = args.profile_name.strip()

    if template_body.get("mode") != "active_controlled":
        print("ERROR: template mode must be 'active_controlled' for enterprise approvals flow", file=sys.stderr)
        return 1

    client = httpx.Client(base_url=args.api_url.rstrip("/"), headers=_headers(token), timeout=60.0)

    r = client.get("/v1/projects")
    r.raise_for_status()
    projects = r.json()
    proj = next((x for x in projects if x.get("slug") == args.project_slug), None)
    if not proj:
        print(
            f"ERROR: No project with slug={args.project_slug!r}. Run scripts/seed_demo.py or create a project.",
            file=sys.stderr,
        )
        return 1
    project_id = proj["id"]
    print(f"Using project: {proj.get('name')} ({project_id})")

    r = client.get(f"/v1/projects/{project_id}/environments")
    r.raise_for_status()
    envs = r.json()
    env = next((e for e in envs if e.get("base_url") == base_url), None)
    if env:
        environment_id = env["id"]
        print(f"Reusing environment: {env.get('name')} ({environment_id})")
    else:
        env_body = {
            "name": args.env_name,
            "env_class": args.env_class,
            "base_url": base_url,
            "allowlist": {"hosts": [host], "path_prefixes": path_prefixes},
        }
        r = client.post(f"/v1/projects/{project_id}/environments", json=env_body)
        if r.status_code == 409:
            print("ERROR: environment name conflict. Use --env-name to choose another name.", file=sys.stderr)
            return 1
        r.raise_for_status()
        environment_id = r.json()["id"]
        print(f"Created environment: {args.env_name} ({environment_id})")

    r = client.get(f"/v1/environments/{environment_id}/scan-profiles")
    r.raise_for_status()
    profiles = r.json()
    existing = next((x for x in profiles if x.get("name") == template_body["name"]), None)
    if existing:
        profile_id = existing["id"]
        matches, drift = _profile_matches(existing, template_body)
        if not matches and not args.allow_existing_drift:
            print("ERROR: existing profile differs from template; refusing drift.", file=sys.stderr)
            print(f"Profile: {template_body['name']} ({profile_id})", file=sys.stderr)
            print("Drift keys: " + ", ".join(drift), file=sys.stderr)
            print("Use --allow-existing-drift to reuse anyway, or choose --profile-name for a new profile.", file=sys.stderr)
            return 2
        if matches:
            print(f"Reusing profile (template-aligned): {template_body['name']} ({profile_id})")
        else:
            print(f"Reusing profile with drift (--allow-existing-drift): {template_body['name']} ({profile_id})")
    else:
        r = client.post(f"/v1/environments/{environment_id}/scan-profiles", json=template_body)
        r.raise_for_status()
        profile_id = r.json()["id"]
        print(f"Created profile from template {template_id}: {template_body['name']} ({profile_id})")

    targets: list[str] = [base_url + "/"]
    if args.target_urls.strip():
        for part in args.target_urls.split(","):
            u = part.strip()
            if u:
                targets.append(u)
    targets = _dedupe_target_urls(targets)

    approval_id = args.approval_id.strip()
    if not approval_id:
        print("\nProfile ready. Next steps:")
        print("1) Request approval:")
        print(
            json.dumps(
                {
                    "profile_id": profile_id,
                    "reason": "Scheduled enterprise active_controlled + fuzz run",
                    "expires_in_hours": 72,
                },
                indent=2,
            )
        )
        print("2) After approval, run with one command:")
        print(
            "python scripts/apply_profile_template.py "
            f"--base-url {base_url} --approval-id <approved-approval-id> "
            f"--project-slug {args.project_slug} --template {Path(args.template).as_posix()}"
        )
        return 0

    body = {"profile_id": profile_id, "approval_id": approval_id, "target_urls": targets}
    r = client.post("/v1/scans", json=body)
    r.raise_for_status()
    scan = r.json()
    scan_id = scan["id"]
    print(f"Started scan: {scan_id} (trace_id={scan.get('trace_id', '')})")

    deadline = time.monotonic() + max(10, int(args.poll_seconds))
    row: dict = {}
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
    api = args.api_url.rstrip("/")
    print(f"\nFindings: {len(findings)}")
    print(f"Markdown report: {api}/v1/reports/{scan_id}.md")
    print(f"JSON report:     {api}/v1/reports/{scan_id}.json")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
