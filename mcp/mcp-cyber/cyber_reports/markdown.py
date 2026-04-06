from __future__ import annotations

from typing import Any


def render_markdown_report(
    *,
    project_name: str,
    environment_name: str,
    scan_id: str,
    summary: dict[str, Any],
    findings: list[dict[str, Any]],
    top_n: int = 10,
) -> str:
    lines = [
        f"# Security posture — {project_name} / {environment_name} — run `{scan_id}`",
        "",
        "## Executive summary",
    ]
    if summary:
        lines.append(f"- Open findings: {summary.get('open_count', '—')}")
        lines.append(f"- By severity: {summary.get('by_severity', {})}")
        lines.append(f"- Delta vs baseline: {summary.get('delta_text', 'n/a')}")
    lines.extend(["", f"## Developer checklist (top {top_n})", ""])
    open_f = [f for f in findings if f.get("status") == "open"]
    open_f.sort(key=lambda x: {"critical": 0, "high": 1, "medium": 2, "low": 3}.get(x.get("severity", "low"), 4))
    for i, f in enumerate(open_f[:top_n], 1):
        lines.append(
            f"{i}. [{f.get('severity', '').upper()}] {f.get('title')} — `{f.get('url') or f.get('affected_asset') or ''}` — rule `{f.get('rule_id')}`"
        )
    qw = [f for f in open_f if "quick_win" in (f.get("tags") or [])]
    if qw:
        lines.extend(["", "## Quick wins", ""])
        for f in qw[:5]:
            lines.append(f"- {f.get('title')} (`{f.get('rule_id')}`)")
    lines.extend(["", "## How to verify fixes", ""])
    lines.append("Re-run a passive scan on the same profile and use compare_scan_runs to confirm fingerprints are resolved.")
    return "\n".join(lines) + "\n"
