"""Run registered adapters in order and normalize findings."""

from __future__ import annotations

import structlog

from cyber_core.models.finding import RawFinding
from cyber_engine.adapters.base import Adapter, ScanContext
from cyber_engine.adapters.active_controlled_stub import ActiveControlledStubAdapter
from cyber_engine.adapters.headers_cookies import HeadersCookiesAdapter
from cyber_engine.adapters.openapi_lint import OpenAPILintAdapter
from cyber_engine.adapters.fuzz_harness_stub import FuzzHarnessStubAdapter
from cyber_engine.adapters.playwright_session import PlaywrightSessionAdapter
from cyber_engine.adapters.routes_json_lint import RoutesJsonLintAdapter
from cyber_engine.adapters.tls_basic import TlsBasicAdapter
from cyber_engine.business_rules import apply_business_rules
from cyber_engine.normalizer import Normalizer
from cyber_engine.policy_engine import PolicyEngine

log = structlog.get_logger()

DEFAULT_ADAPTERS: dict[str, type[Adapter]] = {
    HeadersCookiesAdapter.id: HeadersCookiesAdapter,
    TlsBasicAdapter.id: TlsBasicAdapter,
    OpenAPILintAdapter.id: OpenAPILintAdapter,
    RoutesJsonLintAdapter.id: RoutesJsonLintAdapter,
    PlaywrightSessionAdapter.id: PlaywrightSessionAdapter,
    ActiveControlledStubAdapter.id: ActiveControlledStubAdapter,
    FuzzHarnessStubAdapter.id: FuzzHarnessStubAdapter,
}


def _iter_strings(value: object) -> list[str]:
    if isinstance(value, str):
        return [value]
    if isinstance(value, (int, float, bool)):
        return [str(value)]
    if isinstance(value, list):
        out: list[str] = []
        for item in value:
            out.extend(_iter_strings(item))
        return out
    if isinstance(value, dict):
        out: list[str] = []
        for item in value.values():
            out.extend(_iter_strings(item))
        return out
    return []


def _payload_candidates(options: dict[str, object]) -> list[str]:
    if not isinstance(options, dict):
        return []
    keys = ("payload", "probe", "fuzz", "injection")
    out: list[str] = []
    for key, value in options.items():
        if any(token in str(key).lower() for token in keys):
            out.extend(_iter_strings(value))
    return [x for x in out if x.strip()]


class Orchestrator:
    def __init__(
        self,
        policy: PolicyEngine | None = None,
        norm: Normalizer | None = None,
        adapters: dict[str, type[Adapter]] | None = None,
    ) -> None:
        self.policy = policy or PolicyEngine()
        self.norm = norm or Normalizer()
        self._registry = adapters or DEFAULT_ADAPTERS

    async def run(self, ctx: ScanContext) -> list[dict]:
        base_targets = ctx.target_urls or [ctx.base_url]
        policy_urls = list(dict.fromkeys([*base_targets, *ctx.policy_extra_urls]))
        self.policy.assert_scan_permitted(
            environment_class=ctx.environment_class,
            mode=ctx.mode,
            base_url=ctx.base_url,
            allowlist=ctx.allowlist,
            target_urls=policy_urls,
            approval_id=ctx.options.get("approval_id"),
            approval_status=ctx.options.get("approval_status"),
        )
        self.policy.assert_no_forbidden_payloads(_payload_candidates(ctx.options))
        norm_ctx = {
            "scan_id": ctx.scan_id,
            "project_id": ctx.project_id,
            "environment_id": ctx.environment_id,
        }
        all_raw: list[RawFinding] = []
        for adapter_id in ctx.adapter_ids:
            cls = self._registry.get(adapter_id)
            if not cls:
                log.warning("unknown_adapter", adapter_id=adapter_id)
                continue
            adapter = cls()
            log.info("adapter_start", adapter=adapter_id, scan_id=ctx.scan_id)
            all_raw.extend(await adapter.run(ctx))

        path = ctx.business_rules_path or (ctx.options or {}).get("business_rules_path")
        if isinstance(path, str):
            path = path.strip() or None
        else:
            path = None
        all_raw = apply_business_rules(all_raw, path)
        return self.norm.normalize_batch(all_raw, norm_ctx)
