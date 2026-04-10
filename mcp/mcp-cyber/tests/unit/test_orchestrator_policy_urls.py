from pathlib import Path

import pytest

from cyber_engine.adapters.base import ScanContext
from cyber_engine.orchestrator import Orchestrator
from cyber_engine.policy_engine import PolicyEngine, PolicyError

_DEFAULT_POLICY = str(Path(__file__).resolve().parents[2] / "configs" / "default.policy.yaml")


@pytest.mark.asyncio
async def test_policy_rejects_extra_url_not_allowlisted() -> None:
    ctx = ScanContext(
        scan_id="s",
        trace_id="t",
        profile_id="p",
        mode="passive",
        adapter_ids=[],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="e",
        environment_name="n",
        environment_class="dev",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"]},
        project_id="pr",
        target_urls=["https://example.com/"],
        policy_extra_urls=["https://evil.com/login"],
    )
    orch = Orchestrator(policy=PolicyEngine(_DEFAULT_POLICY))
    with pytest.raises(PolicyError, match="not allowlisted"):
        await orch.run(ctx)


@pytest.mark.asyncio
async def test_policy_rejects_forbidden_payload_options() -> None:
    ctx = ScanContext(
        scan_id="s",
        trace_id="t",
        profile_id="p",
        mode="active_controlled",
        adapter_ids=[],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="e",
        environment_name="n",
        environment_class="dev",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"], "path_prefixes": ["/"]},
        project_id="pr",
        target_urls=["https://example.com/"],
        options={
            "approval_id": "550e8400-e29b-41d4-a716-446655440000",
            "approval_status": "approved",
            "probe_payloads": ["x'; drop table users; --"],
        },
    )
    orch = Orchestrator(policy=PolicyEngine(_DEFAULT_POLICY))
    with pytest.raises(PolicyError, match="forbidden payload"):
        await orch.run(ctx)
