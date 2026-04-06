import pytest

from cyber_engine.adapters.active_controlled_stub import ActiveControlledStubAdapter
from cyber_engine.adapters.base import ScanContext


@pytest.mark.asyncio
async def test_active_stub_empty_when_not_active_mode() -> None:
    ctx = ScanContext(
        scan_id="s",
        trace_id="t",
        profile_id="p",
        mode="passive",
        adapter_ids=["active_controlled_stub"],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="e",
        environment_name="n",
        environment_class="dev",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"]},
        project_id="pr",
    )
    out = await ActiveControlledStubAdapter().run(ctx)
    assert out == []


@pytest.mark.asyncio
async def test_active_stub_emits_info() -> None:
    ctx = ScanContext(
        scan_id="s",
        trace_id="t",
        profile_id="p",
        mode="active_controlled",
        adapter_ids=["active_controlled_stub"],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="e",
        environment_name="n",
        environment_class="staging",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"]},
        project_id="pr",
    )
    out = await ActiveControlledStubAdapter().run(ctx)
    assert len(out) == 1
    assert out[0].rule_id == "active.stub.policy_ok"
    assert out[0].severity == "info"
