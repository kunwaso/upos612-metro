"""
Optional YAML rules applied to RawFinding list after adapters, before normalize.

Tag-only mutations preserve severity/title for safety; rules file is local config.
"""

from __future__ import annotations

import re
from pathlib import Path
from typing import Any

import structlog
import yaml

from cyber_core.models.finding import RawFinding

log = structlog.get_logger()

_MATCH_KEYS = frozenset({"rule_id_prefix", "rule_id_regex", "category_equals", "severity_equals"})


def _match_has_criterion(match: dict[str, Any]) -> bool:
    for k in _MATCH_KEYS:
        v = match.get(k)
        if v is None or v == "":
            continue
        if k == "rule_id_regex" and not isinstance(v, str):
            continue
        return True
    return False


def _load_doc(path: str) -> dict[str, Any]:
    p = Path(path)
    if not p.is_file():
        return {}
    with open(p, encoding="utf-8") as f:
        raw = yaml.safe_load(f)
    return raw if isinstance(raw, dict) else {}


def apply_business_rules(findings: list[RawFinding], path: str | None) -> list[RawFinding]:
    """Augment tags on findings when optional YAML path exists and is readable."""
    if not path:
        return findings
    doc = _load_doc(path)
    rules = doc.get("rules")
    if not rules or not isinstance(rules, list):
        return findings

    compiled: list[tuple[dict[str, Any], re.Pattern[str] | None]] = []
    for r in rules:
        if not isinstance(r, dict):
            continue
        match = r.get("match") or {}
        if not isinstance(match, dict) or not _match_has_criterion(match):
            log.warning("business_rules_skip_rule", name=r.get("name"), reason="empty_or_invalid_match")
            continue
        rx: re.Pattern[str] | None = None
        pat = match.get("rule_id_regex")
        if pat and isinstance(pat, str):
            try:
                rx = re.compile(pat)
            except re.error as e:
                log.warning("business_rules_bad_regex", rule=r.get("name"), error=str(e))
                continue
        compiled.append((r, rx))

    for raw in findings:
        rid = raw.rule_id or ""
        cat = (raw.category or "").lower()
        sev = (raw.severity or "").lower()
        add: list[str] = []
        for rule, rx in compiled:
            m = rule.get("match") or {}
            if not isinstance(m, dict):
                continue
            prefix = m.get("rule_id_prefix")
            if prefix is not None and isinstance(prefix, str) and prefix and not rid.startswith(prefix):
                continue
            if rx is not None and not rx.search(rid):
                continue
            ce = m.get("category_equals")
            if ce is not None and isinstance(ce, str) and ce.lower() != cat:
                continue
            sse = m.get("severity_equals")
            if sse is not None and isinstance(sse, str) and sse.lower() != sev:
                continue
            tags = rule.get("add_tags") or []
            if isinstance(tags, list):
                for t in tags:
                    if isinstance(t, str) and t:
                        add.append(t)

        if add:
            seen = set(raw.tags or [])
            for t in add:
                if t not in seen:
                    raw.tags.append(t)
                    seen.add(t)

    if compiled:
        log.info("business_rules_applied", path=path, rules=len(compiled), findings=len(findings))
    return findings
