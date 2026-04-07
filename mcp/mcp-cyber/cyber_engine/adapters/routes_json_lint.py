"""
Lint CI-exported route lists (RBAC / exposure hints).

Expected JSON (flexible):
  { "routes": [ { "method": "GET", "path": "/api/admin", "requires_auth": false, "middleware": ["web"] }, ... ] }

Or a single route with ``uri``: ``"POST /api/items"``.

Flags:
- Explicit ``requires_auth: false`` on sensitive path prefixes (admin, internal, debug, actuator, manage).
- State-changing methods without ``requires_auth: true`` on ``/api/`` paths.
"""

from __future__ import annotations

import json
import re
from typing import Any

from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext

_SENSITIVE = re.compile(r"/(admin|internal|debug|actuator|manage)(/|$)", re.IGNORECASE)

_MUTATING = frozenset({"POST", "PUT", "PATCH", "DELETE"})


def _parse_route(obj: dict[str, Any]) -> tuple[str, str, bool | None, list[str]]:
    method = str(obj.get("method") or "").strip().upper()
    path = str(obj.get("path") or obj.get("uri") or "").strip()
    uri = str(obj.get("uri") or "").strip()
    if uri and not path:
        parts = uri.split(None, 1)
        if len(parts) == 2:
            method, path = parts[0].upper(), parts[1]
        elif len(parts) == 1 and parts[0].startswith("/"):
            path = parts[0]
    req_auth = obj.get("requires_auth")
    if req_auth is not None:
        req_auth_bool: bool | None = bool(req_auth)
    else:
        req_auth_bool = None
    mw = obj.get("middleware") or obj.get("middlewares") or []
    mw_list = [str(x).lower() for x in mw] if isinstance(mw, list) else []
    return method, path, req_auth_bool, mw_list


class RoutesJsonLintAdapter(Adapter):
    id = "routes_json_lint"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        raw = ctx.options.get("routes_json")
        if not raw or not str(raw).strip():
            return []
        try:
            doc = json.loads(str(raw))
        except json.JSONDecodeError:
            return [
                RawFinding(
                    rule_id="routes_json.invalid",
                    category="rbac",
                    title="routes_json is not valid JSON",
                    severity="medium",
                    confidence=1.0,
                    remediation=RemediationBlock(summary="Export valid JSON from your framework route list."),
                )
            ]
        routes = doc.get("routes") if isinstance(doc, dict) else None
        if not isinstance(routes, list):
            return [
                RawFinding(
                    rule_id="routes_json.routes_missing",
                    category="rbac",
                    title="routes_json must contain a 'routes' array",
                    severity="low",
                    confidence=0.9,
                    remediation=RemediationBlock(
                        summary='Use shape { "routes": [ { "method", "path", "requires_auth" }, ... ] }',
                    ),
                )
            ]

        markers = ctx.options.get("routes_auth_middleware_markers") or ["auth", "jwt", "sanctum", "authorize"]
        if isinstance(markers, str):
            markers = [markers]
        marker_set = {str(m).lower() for m in markers if m}

        findings: list[RawFinding] = []
        for i, item in enumerate(routes):
            if not isinstance(item, dict):
                continue
            method, path, req_auth, mw_list = _parse_route(item)
            if not path or path == "/":
                continue
            p_low = path.lower()

            if req_auth is False and _SENSITIVE.search(p_low):
                findings.append(
                    RawFinding(
                        rule_id="rbac.route.sensitive_marked_public",
                        category="rbac",
                        title=f"Route marked unauthenticated but matches sensitive pattern: {method} {path}",
                        severity="high",
                        confidence=0.75,
                        url=None,
                        parameter=f"routes[{i}]",
                        reproduction="requires_auth is false in CI routes export.",
                        root_cause="Sensitive endpoints should require authentication unless intentionally public.",
                        remediation=RemediationBlock(
                            summary="Confirm in application code; fix export or add auth middleware.",
                            steps=[],
                        ),
                        tags=["rbac", "ci"],
                    )
                )

            if (
                method in _MUTATING
                and "/api/" in p_low
                and req_auth is False
            ):
                findings.append(
                    RawFinding(
                        rule_id="rbac.route.api_mutation_public",
                        category="rbac",
                        title=f"State-changing API route marked public: {method} {path}",
                        severity="medium",
                        confidence=0.55,
                        parameter=f"routes[{i}]",
                        remediation=RemediationBlock(
                            summary="Verify the route is safe without authentication.",
                        ),
                        tags=["rbac", "ci"],
                    )
                )

            if req_auth is None and _SENSITIVE.search(p_low) and mw_list:
                if not marker_set.intersection(mw_list):
                    findings.append(
                        RawFinding(
                            rule_id="rbac.route.sensitive_no_auth_middleware",
                            category="rbac",
                            title=f"Sensitive path has middleware but none match auth markers: {method} {path}",
                            severity="medium",
                            confidence=0.5,
                            parameter=f"routes[{i}]",
                            reproduction=f"middleware={mw_list!s}; expected one of {sorted(marker_set)!s}",
                            remediation=RemediationBlock(
                                summary="Align routes_auth_middleware_markers in profile options with your stack.",
                            ),
                            tags=["rbac", "ci"],
                        )
                    )

        return findings
