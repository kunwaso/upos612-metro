"""
Authenticated browser flows (Phase 2).

Feature-flagged adapter. When CYBER_PLAYWRIGHT_ADAPTER=1 and Playwright is installed,
this can drive login flows using credentials from vault references (credential_ref on ScanProfile).

MVP: stub only — does not launch browsers. Keeps allowlist + policy gates in the orchestrator.
"""

from __future__ import annotations

import os

import structlog
from cyber_core.models.finding import RawFinding

from cyber_engine.adapters.base import Adapter, ScanContext

log = structlog.get_logger()


class PlaywrightSessionAdapter(Adapter):
    id = "playwright_session"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        if os.environ.get("CYBER_PLAYWRIGHT_ADAPTER", "").lower() not in ("1", "true", "yes"):
            log.info("playwright_adapter_disabled", scan_id=ctx.scan_id)
            return []
        log.warning(
            "playwright_adapter_stub",
            message="Install playwright, implement login recipe, and enable CYBER_PLAYWRIGHT_ADAPTER.",
        )
        return []
