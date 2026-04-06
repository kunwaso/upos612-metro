import pytest

from cyber_engine.adapters.base import ScanContext
from cyber_engine.orchestrator import Orchestrator
from cyber_engine.policy_engine import PolicyError


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
    orch = Orchestrator()
    with pytest.raises(PolicyError, match="not allowlisted"):
        await orch.run(ctx)
