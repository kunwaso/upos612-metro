import pytest

from cyber_engine.adapters.base import ScanContext
from cyber_engine.adapters.routes_json_lint import RoutesJsonLintAdapter


@pytest.mark.asyncio
async def test_routes_json_empty_when_no_payload() -> None:
    ctx = ScanContext(
        scan_id="s",
        trace_id="t",
        profile_id="p",
        mode="passive",
        adapter_ids=["routes_json_lint"],
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
    assert await RoutesJsonLintAdapter().run(ctx) == []


@pytest.mark.asyncio
async def test_sensitive_public_flagged() -> None:
    import json

    payload = json.dumps(
        {
            "routes": [
                {"method": "GET", "path": "/api/admin/users", "requires_auth": False},
            ]
        }
    )
    ctx = ScanContext(
        scan_id="s",
        trace_id="t",
        profile_id="p",
        mode="passive",
        adapter_ids=["routes_json_lint"],
        rate_limit_rps=1.0,
        max_concurrency=1,
        environment_id="e",
        environment_name="n",
        environment_class="dev",
        base_url="https://example.com",
        allowlist={"hosts": ["example.com"]},
        project_id="pr",
        options={"routes_json": payload},
    )
    out = await RoutesJsonLintAdapter().run(ctx)
    assert any(f.rule_id == "rbac.route.sensitive_marked_public" for f in out)
