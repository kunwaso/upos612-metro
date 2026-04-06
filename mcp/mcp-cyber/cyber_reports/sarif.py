"""SARIF 2.1.0 subset for CI ingestion (GitHub/GitLab compatible subset)."""

from __future__ import annotations

from typing import Any


def build_sarif(
    *,
    tool_name: str = "mcp-cyber",
    scan_id: str,
    findings: list[dict[str, Any]],
) -> dict[str, Any]:
    rules: dict[str, dict] = {}
    results: list[dict] = []
    for f in findings:
        rid = f.get("rule_id", "unknown")
        if rid not in rules:
            rules[rid] = {
                "id": rid,
                "name": f.get("title", rid),
                "shortDescription": {"text": f.get("title", rid)},
                "fullDescription": {"text": f.get("root_cause") or f.get("title", "")},
            }
        results.append(
            {
                "ruleId": rid,
                "level": _severity_to_level(f.get("severity", "note")),
                "message": {"text": f.get("title", rid)},
                "locations": [
                    {
                        "physicalLocation": {
                            "artifactLocation": {"uri": f.get("url") or f.get("affected_asset") or "unknown"}
                        }
                    }
                ],
                "properties": {
                    "fingerprint": f.get("fingerprint"),
                    "confidence": f.get("confidence"),
                    "scanId": scan_id,
                },
            }
        )
    return {
        "$schema": "https://raw.githubusercontent.com/oasis-tcs/sarif-spec/master/Schemata/sarif-schema-2.1.0.json",
        "version": "2.1.0",
        "runs": [
            {
                "tool": {
                    "driver": {"name": tool_name, "version": "0.1.0", "rules": list(rules.values())}
                },
                "results": results,
            }
        ],
    }


def _severity_to_level(sev: str) -> str:
    s = sev.lower()
    if s in ("critical", "high"):
        return "error"
    if s == "medium":
        return "warning"
    return "note"
