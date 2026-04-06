"""Developer remediation checklist payload (e.g. for ticketing integrations)."""

from __future__ import annotations

from typing import Any


def build_remediation_checklist(findings: list[dict[str, Any]]) -> dict[str, Any]:
    tasks: list[dict[str, Any]] = []
    for f in findings:
        if f.get("status") != "open":
            continue
        rem = f.get("remediation") or {}
        tasks.append(
            {
                "title": f"[{f.get('severity')}] {f.get('title')}",
                "rule_id": f.get("rule_id"),
                "fingerprint": f.get("fingerprint"),
                "url": f.get("url"),
                "summary": rem.get("summary") if isinstance(rem, dict) else "",
                "steps": rem.get("steps", []) if isinstance(rem, dict) else [],
                "reproduction": f.get("reproduction"),
            }
        )
    return {"version": 1, "task_count": len(tasks), "tasks": tasks}
