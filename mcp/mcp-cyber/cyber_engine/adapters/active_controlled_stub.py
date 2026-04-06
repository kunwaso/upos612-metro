"""
Controlled-active placeholder (Phase 3).

Performs no intrusive payloads. Emits an informational finding so runs using
``active_controlled`` mode + approved ``approval_id`` produce auditable output.
Replace with policy-tied safe probes in a later iteration.
"""

from __future__ import annotations

import structlog
from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext

log = structlog.get_logger()


class ActiveControlledStubAdapter(Adapter):
    id = "active_controlled_stub"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        if ctx.mode != "active_controlled":
            return []
        log.info("active_controlled_stub_run", scan_id=ctx.scan_id)
        return [
            RawFinding(
                rule_id="active.stub.policy_ok",
                category="process",
                title="Controlled active scan executed (stub — no intrusive payloads)",
                severity="info",
                confidence=1.0,
                url=ctx.base_url,
                reproduction="Policy permitted active_controlled; this adapter records execution only.",
                root_cause="Enterprise Phase 3 placeholder until safe payload modules are registered.",
                remediation=RemediationBlock(
                    summary="Add allowlisted safe probes under policy.active_scan rules.",
                    steps=["Keep approval workflow for any real active testing"],
                ),
                tags=["phase3", "audit"],
            )
        ]
