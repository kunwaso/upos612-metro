"""Pure helpers to shape analytics query rows (testable without DB)."""

from __future__ import annotations

import uuid
from collections import defaultdict
from typing import Any


def fold_fleet_rows(
    rows: list[tuple[uuid.UUID, str, str, str, int]],
) -> list[dict[str, Any]]:
    """
    rows: (project_id, slug, name, severity, count) for open findings per severity.
    """
    by_proj: dict[uuid.UUID, dict[str, Any]] = {}
    for pid, slug, name, severity, n in rows:
        if pid not in by_proj:
            by_proj[pid] = {
                "project_id": str(pid),
                "slug": slug,
                "name": name,
                "open_by_severity": {},
                "open_total": 0,
            }
        by_proj[pid]["open_by_severity"][severity] = n
        by_proj[pid]["open_total"] += int(n)
    return list(by_proj.values())


def fold_trend_rows(
    rows: list[tuple[str, str, int]],
) -> list[dict[str, Any]]:
    """
    rows: (day_str YYYY-MM-DD, severity, count).
    """
    by_day: dict[str, dict[str, int]] = defaultdict(lambda: defaultdict(int))
    for day, sev, n in rows:
        by_day[day][sev] += int(n)
    out = []
    for day in sorted(by_day.keys()):
        sevs = dict(by_day[day])
        out.append({"date": day, "by_severity": sevs, "total": sum(sevs.values())})
    return out
