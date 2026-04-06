"""Normalize RawFinding → DB-ready dict + deduplication fingerprint."""

from __future__ import annotations

import hashlib
from typing import Any
from uuid import UUID

from cyber_core.models.finding import RawFinding


def compute_fingerprint(
    rule_id: str,
    url: str | None,
    parameter: str | None,
    variant: str = "",
) -> str:
    raw = "|".join([rule_id, url or "", parameter or "", variant])
    h = hashlib.sha256(raw.encode("utf-8")).hexdigest()
    return f"sha256:{h}"


class Normalizer:
    def normalize(self, raw: RawFinding, ctx: dict[str, Any]) -> dict[str, Any]:
        fp = compute_fingerprint(raw.rule_id, raw.url, raw.parameter)
        remediation = raw.remediation.model_dump()
        return {
            "rule_id": raw.rule_id,
            "category": raw.category,
            "title": raw.title,
            "severity": raw.severity,
            "confidence": float(raw.confidence),
            "cvss_score": raw.cvss_score,
            "affected_asset": raw.affected_asset,
            "url": raw.url,
            "component": raw.component,
            "parameter": raw.parameter,
            "fingerprint": fp,
            "evidence": raw.evidence,
            "reproduction": raw.reproduction,
            "root_cause": raw.root_cause,
            "remediation": remediation,
            "external_refs": raw.references,
            "tags": raw.tags,
            "project_id": UUID(ctx["project_id"]),
            "environment_id": UUID(ctx["environment_id"]),
            "scan_id": UUID(ctx["scan_id"]),
        }

    def normalize_batch(self, raws: list[RawFinding], ctx: dict[str, Any]) -> list[dict[str, Any]]:
        return [self.normalize(r, ctx) for r in raws]
