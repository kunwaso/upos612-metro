"""
Evidence artifacts (screenshots, redacted HAR): local filesystem under CYBER_EVIDENCE_DIR.

S3-compatible storage can be added behind the same helpers later (presigned URLs).
"""

from __future__ import annotations

import os
import re
from pathlib import Path


def evidence_root() -> Path:
    return Path(os.environ.get("CYBER_EVIDENCE_DIR", "./data/evidence"))


def ensure_evidence_dir() -> Path:
    root = evidence_root()
    root.mkdir(parents=True, exist_ok=True)
    return root


_SAFE_NAME = re.compile(r"[^a-zA-Z0-9._-]+")


def _safe_scan_segment(scan_id: str) -> str:
    s = scan_id.replace("..", "_").strip()
    return _SAFE_NAME.sub("_", s)[:80] or "scan"


def _safe_filename(name: str) -> str:
    base = Path(name).name
    return _SAFE_NAME.sub("_", base)[:120] or "artifact.bin"


def store_scan_artifact(scan_id: str, filename: str, data: bytes) -> str:
    """
    Write bytes under evidence_root()/<scan_id>/<filename>; return file: URI for Finding.evidence.
    """
    root = ensure_evidence_dir() / _safe_scan_segment(scan_id)
    root.mkdir(parents=True, exist_ok=True)
    path = root / _safe_filename(filename)
    path.write_bytes(data)
    return f"file:{path.resolve().as_posix()}"


def signed_url_if_supported(uri: str, *, ttl_seconds: int = 3600) -> str:
    """Local file URIs are returned as-is; future S3 paths would return presigned GET."""
    _ = ttl_seconds
    return uri
