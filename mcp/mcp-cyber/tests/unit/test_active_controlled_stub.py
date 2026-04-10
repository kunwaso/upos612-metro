import httpx
import pytest

from cyber_engine.adapters.active_controlled_stub import ActiveControlledStubAdapter
from cyber_engine.adapters.base import ScanContext


def _ctx(mode: str, client: httpx.AsyncClient | None = None) -> ScanContext:
    return ScanContext(
        scan_id="00000000-0000-4000-8000-000000000001",
        trace_id="t",
        profile_id="p",
        mode=mode,
        adapter_ids=["active_controlled_stub"],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="00000000-0000-4000-8000-000000000002",
        environment_name="staging",
        environment_class="staging",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"], "path_prefixes": ["/"]},
        project_id="00000000-0000-4000-8000-000000000003",
        target_urls=["https://example.com/app"],
        options={},
        http_client=client,
    )


@pytest.mark.asyncio
async def test_active_stub_empty_when_not_active_mode() -> None:
    out = await ActiveControlledStubAdapter().run(_ctx("passive"))
    assert out == []


@pytest.mark.asyncio
async def test_active_adapter_finds_method_redirect_and_error_signals() -> None:
    async def handler(request: httpx.Request) -> httpx.Response:
        params = dict(request.url.params)
        if request.method == "OPTIONS":
            return httpx.Response(200, headers={"Allow": "GET, POST, TRACE, OPTIONS"})
        if request.method == "TRACE":
            return httpx.Response(200, text="TRACE / HTTP/1.1")
        if "next" in params:
            return httpx.Response(302, headers={"Location": "https://outside.example/login"})
        if "__cyber_probe" in params:
            return httpx.Response(500, text="Traceback (most recent call last): RuntimeError")
        return httpx.Response(200, text="ok")

    transport = httpx.MockTransport(handler)
    async with httpx.AsyncClient(transport=transport) as client:
        out = await ActiveControlledStubAdapter().run(_ctx("active_controlled", client))

    rule_ids = {f.rule_id for f in out}
    assert "active.method.trace_advertised" in rule_ids
    assert "active.method.trace_enabled" in rule_ids
    assert "active.redirect.open_redirect" in rule_ids
    assert "active.robustness.unhandled_error" in rule_ids
    assert "active.errors.stacktrace_disclosure" in rule_ids
