import httpx
import pytest

from cyber_engine.adapters.base import ScanContext
from cyber_engine.adapters.fuzz_harness_stub import FuzzHarnessStubAdapter


def _ctx(mode: str, client: httpx.AsyncClient | None = None) -> ScanContext:
    return ScanContext(
        scan_id="00000000-0000-4000-8000-000000000011",
        trace_id="t",
        profile_id="p",
        mode=mode,
        adapter_ids=["fuzz_harness_stub"],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="00000000-0000-4000-8000-000000000012",
        environment_name="staging",
        environment_class="staging",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"], "path_prefixes": ["/"]},
        project_id="00000000-0000-4000-8000-000000000013",
        target_urls=["https://example.com/search"],
        options={},
        http_client=client,
    )


@pytest.mark.asyncio
async def test_fuzz_adapter_off_when_not_active_mode() -> None:
    out = await FuzzHarnessStubAdapter().run(_ctx("passive"))
    assert out == []


@pytest.mark.asyncio
async def test_fuzz_adapter_detects_server_error_and_error_disclosure() -> None:
    async def handler(request: httpx.Request) -> httpx.Response:
        if request.url.params:
            return httpx.Response(500, text="SQLSTATE[42000]: syntax error")
        return httpx.Response(200, text="ok")

    transport = httpx.MockTransport(handler)
    async with httpx.AsyncClient(transport=transport) as client:
        out = await FuzzHarnessStubAdapter().run(_ctx("active_controlled", client))

    rule_ids = {f.rule_id for f in out}
    assert "fuzz.robustness.server_error" in rule_ids
    assert "fuzz.errors.disclosure" in rule_ids
