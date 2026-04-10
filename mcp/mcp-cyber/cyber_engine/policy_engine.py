"""Central policy gate: environment class × scan mode × approval."""

from __future__ import annotations

import os
from pathlib import Path
from typing import Any

import yaml
import structlog

from cyber_engine.allowlist import AllowlistError, assert_urls_allowed

log = structlog.get_logger()


class PolicyError(RuntimeError):
    """Scan blocked by policy."""


def _load_yaml(path: str | None) -> dict[str, Any]:
    p = path or os.environ.get("CYBER_POLICY_PATH", "")
    if not p or not Path(p).is_file():
        return {}
    with open(p, encoding="utf-8") as f:
        return yaml.safe_load(f) or {}


class PolicyEngine:
    def __init__(self, policy_path: str | None = None) -> None:
        self._doc = _load_yaml(policy_path)
        self._global = self._doc.get("global", {})
        self._env_rules = self._doc.get("environment_rules", {})
        self._active = self._doc.get("active_scan", {})
        self._gates = self._doc.get("gates", {})

    @property
    def require_allowlist(self) -> bool:
        env = os.environ.get("CYBER_REQUIRE_ALLOWLIST", "").lower()
        if env == "true":
            return True
        if env == "false":
            return False
        return bool(self._global.get("require_allowlist", True))

    @property
    def kill_switch(self) -> bool:
        return bool(self._global.get("kill_switch", False))

    def assert_scan_permitted(
        self,
        *,
        environment_class: str,
        mode: str,
        base_url: str,
        allowlist: dict,
        target_urls: list[str],
        approval_id: str | None,
        approval_status: str | None,
    ) -> None:
        if self.kill_switch:
            raise PolicyError("Global policy kill_switch is enabled; scans are blocked.")

        rules = self._env_rules.get(environment_class) or self._env_rules.get(
            environment_class.lower(), {}
        )
        allowed_modes = rules.get("allowed_modes") or ["passive"]
        if mode not in allowed_modes:
            raise PolicyError(
                f"Mode {mode!r} not allowed for environment class {environment_class!r}; "
                f"allowed: {allowed_modes}"
            )

        if environment_class == "prod" and mode != "passive":
            raise PolicyError("Production environments allow passive scans only.")

        if mode == "active_controlled":
            if rules.get("active_requires_approval", True):
                if not approval_id or approval_status != "approved":
                    raise PolicyError(
                        "Controlled active scan requires an approved approval_id for this environment."
                    )

        urls = target_urls if target_urls else [base_url]
        try:
            assert_urls_allowed(
                urls, base_url, allowlist, require_allowlist=self.require_allowlist
            )
        except AllowlistError as e:
            raise PolicyError(str(e)) from e

        log.info(
            "policy_permitted",
            environment_class=environment_class,
            mode=mode,
            target_count=len(urls),
        )

    def forbidden_payload_hit(self, text: str) -> bool:
        patterns = self._active.get("forbid_payloads_matching") or []
        t = text.lower()
        return any(p.lower() in t for p in patterns)

    def assert_no_forbidden_payloads(self, payloads: list[str]) -> None:
        for payload in payloads:
            if self.forbidden_payload_hit(payload):
                raise PolicyError("Scan blocked by policy: forbidden payload pattern detected.")

    def should_block_findings(
        self,
        findings: list[dict[str, Any]],
        *,
        gate_name: str = "ci_default",
    ) -> bool:
        gate = self._gates.get(gate_name) or {}
        rules = gate.get("block_if") or []
        if not rules:
            return False
        for finding in findings:
            sev = str(finding.get("severity") or "").lower()
            st = str(finding.get("status") or "").lower()
            cat = str(finding.get("category") or "").lower()
            for rule in rules:
                r_sev = str(rule.get("severity") or "").lower()
                r_status = str(rule.get("status") or "").lower()
                categories = [str(c).lower() for c in (rule.get("categories") or [])]
                if r_sev and sev != r_sev:
                    continue
                if r_status and st != r_status:
                    continue
                if categories and cat not in categories:
                    continue
                return True
        return False
