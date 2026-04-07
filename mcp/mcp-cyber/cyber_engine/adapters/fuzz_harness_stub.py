"""
Allowlisted fuzz / external-harness integration slot (Phase 5).

Does not send traffic or invoke external tools unless profile options enable it.
When ``enable_fuzz_harness_stub`` is set on the scan profile (or run options),
emits a single informational finding documenting the hook for a real harness.
"""

from __future__ import annotations

import os

import structlog
from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext

log = structlog.get_logger()


def _fuzz_stub_enabled(ctx: ScanContext) -> bool:
    if ctx.options.get("enable_fuzz_harness_stub"):
        return True
    return os.environ.get("CYBER_FUZZ_STUB_ENABLED", "").lower() in ("1", "true", "yes")


class FuzzHarnessStubAdapter(Adapter):
    id = "fuzz_harness_stub"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        if not _fuzz_stub_enabled(ctx):
            return []
        log.info("fuzz_harness_stub_run", scan_id=ctx.scan_id)
        return [
            RawFinding(
                rule_id="fuzz.stub.harness_slot",
                category="process",
                title="Fuzz / external harness slot (stub — no payloads executed)",
                severity="info",
                confidence=1.0,
                url=ctx.base_url,
                reproduction="Stub only: wire CYBER_FUZZ_STUB_ENABLED or profile option "
                "enable_fuzz_harness_stub, then replace this adapter with an allowlisted harness.",
                root_cause="Integration point for business-approved, allowlisted fuzzing or DAST callbacks.",
                remediation=RemediationBlock(
                    summary="Use policy + approvals before attaching any real fuzz engine.",
                    steps=[
                        "Keep targets strictly allowlisted",
                        "Run fuzz/DAST only in dev/staging unless explicitly approved",
                    ],
                ),
                tags=["phase5", "fuzz_hook", "audit"],
            )
        ]
