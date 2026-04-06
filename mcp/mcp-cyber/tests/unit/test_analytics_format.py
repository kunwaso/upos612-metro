import uuid

from cyber_reports.analytics_format import fold_fleet_rows, fold_trend_rows


def test_fold_fleet_rows_empty() -> None:
    assert fold_fleet_rows([]) == []


def test_fold_fleet_rows_two_severities() -> None:
    p = uuid.UUID("00000000-0000-4000-8000-0000000000aa")
    rows = [
        (p, "demo", "Demo", "high", 2),
        (p, "demo", "Demo", "medium", 5),
    ]
    out = fold_fleet_rows(rows)
    assert len(out) == 1
    assert out[0]["open_total"] == 7
    assert out[0]["open_by_severity"]["high"] == 2
    assert out[0]["open_by_severity"]["medium"] == 5


def test_fold_trend_rows() -> None:
    raw = [
        ("2026-04-01", "high", 1),
        ("2026-04-01", "low", 3),
        ("2026-04-02", "high", 2),
    ]
    s = fold_trend_rows(raw)
    assert len(s) == 2
    assert s[0]["date"] == "2026-04-01"
    assert s[0]["total"] == 4
    assert s[1]["total"] == 2
