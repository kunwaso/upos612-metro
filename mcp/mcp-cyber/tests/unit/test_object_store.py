from pathlib import Path

from cyber_engine.evidence.object_store import ensure_evidence_dir, store_scan_artifact


def test_store_scan_artifact_writes_file(tmp_path, monkeypatch) -> None:
    monkeypatch.setenv("CYBER_EVIDENCE_DIR", str(tmp_path / "ev"))
    ensure_evidence_dir()
    uri = store_scan_artifact("scan-abc", "x.png", b"hello")
    assert uri.startswith("file:")
    path = Path(uri.replace("file:", ""))
    assert path.is_file()
    assert path.read_bytes() == b"hello"
