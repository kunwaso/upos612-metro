"""
Object storage for screenshots/HAR/redacted snippets (Phase 2+).

MVP: store local paths in Finding.evidence JSON; use filesystem under CYBER_EVIDENCE_DIR.
Enterprise: S3-compatible API with server-side encryption + signed GET URLs.

Never store raw session tokens in evidence; redact at capture time.
"""

from __future__ import annotations

import os
from pathlib import Path


def evidence_root() -> Path:
    return Path(os.environ.get("CYBER_EVIDENCE_DIR", "./data/evidence"))


def ensure_evidence_dir() -> Path:
    root = evidence_root()
    root.mkdir(parents=True, exist_ok=True)
    return root
