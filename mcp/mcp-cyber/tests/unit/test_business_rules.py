"""Phase 5 business_rules YAML tag enrichment."""

from __future__ import annotations

from pathlib import Path

from cyber_core.models.finding import RawFinding
from cyber_engine.business_rules import apply_business_rules


def _rf(rule_id: str = "hdr.cache", **kw) -> RawFinding:
    return RawFinding(
        rule_id=rule_id,
        category=kw.get("category", "headers"),
        title="t",
        severity=kw.get("severity", "low"),
        confidence=0.5,
    )


def test_prefix_adds_tags(tmp_path: Path) -> None:
    p = tmp_path / "r.yaml"
    p.write_text(
        """
version: 1
rules:
  - name: hdr
    match:
      rule_id_prefix: "hdr."
    add_tags: [quick_review]
""",
        encoding="utf-8",
    )
    a = _rf("hdr.cache")
    b = _rf("other.rule")
    out = apply_business_rules([a, b], str(p))
    assert "quick_review" in out[0].tags
    assert out[1].tags == []


def test_no_path_noop() -> None:
    r = _rf()
    out = apply_business_rules([r], None)
    assert out[0].tags == []


def test_empty_match_rule_skipped(tmp_path: Path) -> None:
    p = tmp_path / "r.yaml"
    p.write_text(
        """
version: 1
rules:
  - name: bad
    match: {}
    add_tags: [should_not_apply]
""",
        encoding="utf-8",
    )
    r = _rf("anything")
    out = apply_business_rules([r], str(p))
    assert "should_not_apply" not in r.tags


def test_regex_match(tmp_path: Path) -> None:
    p = tmp_path / "r.yaml"
    p.write_text(
        r"""
version: 1
rules:
  - name: openapi
    match:
      rule_id_regex: '^openapi\.'
    add_tags: [contract]
""",
        encoding="utf-8",
    )
    r = _rf("openapi.missing_field")
    out = apply_business_rules([r], str(p))
    assert "contract" in out[0].tags


def test_severity_and_category_match(tmp_path: Path) -> None:
    p = tmp_path / "r.yaml"
    p.write_text(
        """
version: 1
rules:
  - name: sev
    match:
      category_equals: "process"
      severity_equals: "high"
    add_tags: [prio]
""",
        encoding="utf-8",
    )
    r = RawFinding(
        rule_id="x",
        category="process",
        title="t",
        severity="high",
        confidence=1.0,
    )
    apply_business_rules([r], str(p))
    assert "prio" in r.tags


def test_tag_dedupe(tmp_path: Path) -> None:
    p = tmp_path / "r.yaml"
    p.write_text(
        """
version: 1
rules:
  - match:
      rule_id_prefix: "h"
    add_tags: [a]
  - match:
      rule_id_prefix: "hdr"
    add_tags: [a, b]
""",
        encoding="utf-8",
    )
    r = _rf("hdr.x")
    apply_business_rules([r], str(p))
    assert r.tags.count("a") == 1
    assert "b" in r.tags
