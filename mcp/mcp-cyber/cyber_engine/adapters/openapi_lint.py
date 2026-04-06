"""Lightweight OpenAPI document checks (MVP; Spectral can wrap later)."""

from __future__ import annotations

import json
from typing import Any

from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext


class OpenAPILintAdapter(Adapter):
    id = "openapi_lint"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        spec_text = ctx.options.get("openapi_json")
        if not spec_text:
            return []
        try:
            spec: dict[str, Any] = json.loads(spec_text)
        except json.JSONDecodeError:
            return [
                RawFinding(
                    rule_id="openapi.invalid_json",
                    category="openapi",
                    title="OpenAPI artifact is not valid JSON",
                    severity="medium",
                    confidence=1.0,
                    remediation=RemediationBlock(summary="Upload a valid OpenAPI 3.x JSON document."),
                )
            ]
        findings: list[RawFinding] = []
        if "openapi" not in spec and "swagger" not in spec:
            findings.append(
                RawFinding(
                    rule_id="openapi.version.missing",
                    category="openapi",
                    title="OpenAPI document missing openapi/swagger version field",
                    severity="low",
                    confidence=0.9,
                    remediation=RemediationBlock(summary="Use OpenAPI 3.x with top-level openapi field."),
                )
            )
        components = spec.get("components", {})
        schemes = spec.get("security", []) or components.get("securitySchemes", {})
        paths = spec.get("paths") or {}
        if paths and not spec.get("security") and not schemes:
            findings.append(
                RawFinding(
                    rule_id="openapi.security.not_defined",
                    category="openapi",
                    title="No global or component security schemes defined for paths",
                    severity="medium",
                    confidence=0.5,
                    remediation=RemediationBlock(
                        summary="Define securitySchemes and apply security requirements to sensitive paths.",
                        steps=[],
                    ),
                )
            )
        return findings
