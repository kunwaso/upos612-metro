import pytest

from cyber_engine.adapters.base import ScanContext
from cyber_engine.adapters.fuzz_harness_stub import FuzzHarnessStubAdapter


@pytest.mark.asyncio
async def test_fuzz_stub_off_by_default() -> None:
    ctx = ScanContext(
        scan_id="s",
        trace_id="t",
        profile_id="p",
        mode="passive",
        adapter_ids=["fuzz_harness_stub"],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="e",
        environment_name="n",
        environment_class="dev",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"]},
        project_id="pr",
        options={},
    )
    out = await FuzzHarnessStubAdapter().run(ctx)
    assert out == []


@pytest.mark.asyncio
async def test_fuzz_stub_via_option() -> None:
    ctx = ScanContext(
        scan_id="s",
        trace_id="t",
        profile_id="p",
        mode="passive",
        adapter_ids=["fuzz_harness_stub"],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="e",
        environment_name="n",
        environment_class="dev",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"]},
        project_id="pr",
        options={"enable_fuzz_harness_stub": True},
    )
    out = await FuzzHarnessStubAdapter().run(ctx)
    assert len(out) == 1
    assert out[0].rule_id == "fuzz.stub.harness_slot"
    assert out[0].severity == "info"
