import pytest
from pathlib import Path

from cyber_engine.policy_engine import PolicyEngine, PolicyError


_DEFAULT_POLICY = str(Path(__file__).resolve().parents[2] / "configs" / "default.policy.yaml")


@pytest.fixture
def policy(tmp_path):
    p = tmp_path / "pol.yaml"
    p.write_text(
        """
global:
  require_allowlist: false
  kill_switch: false
environment_rules:
  prod:
    allowed_modes: [passive]
  staging:
    allowed_modes: [passive, authenticated_passive, active_controlled]
    active_requires_approval: true
""",
        encoding="utf-8",
    )
    return PolicyEngine(str(p))


def test_prod_blocks_active(policy: PolicyEngine):
    with pytest.raises(PolicyError, match="not allowed"):
        policy.assert_scan_permitted(
            environment_class="prod",
            mode="active_controlled",
            base_url="https://a.example",
            allowlist={},
            target_urls=["https://a.example/"],
            approval_id=None,
            approval_status=None,
        )


def test_staging_active_requires_approval(policy: PolicyEngine):
    with pytest.raises(PolicyError, match="approved"):
        policy.assert_scan_permitted(
            environment_class="staging",
            mode="active_controlled",
            base_url="https://a.example",
            allowlist={},
            target_urls=["https://a.example/"],
            approval_id=None,
            approval_status=None,
        )


def test_staging_active_with_approval(policy: PolicyEngine):
    policy.assert_scan_permitted(
        environment_class="staging",
        mode="active_controlled",
        base_url="https://a.example",
        allowlist={},
        target_urls=["https://a.example/"],
        approval_id="550e8400-e29b-41d4-a716-446655440000",
        approval_status="approved",
    )


def test_passive_prod_ok(policy: PolicyEngine):
    policy.assert_scan_permitted(
        environment_class="prod",
        mode="passive",
        base_url="https://a.example",
        allowlist={},
        target_urls=["https://a.example/"],
        approval_id=None,
        approval_status=None,
    )


def test_forbidden_payload_guard_blocks(policy: PolicyEngine):
    policy = PolicyEngine(_DEFAULT_POLICY)
    with pytest.raises(PolicyError, match="forbidden payload"):
        policy.assert_no_forbidden_payloads(["SELECT * FROM users WHERE a='; drop table users"])


def test_ci_gate_block_if_high_authz() -> None:
    p = PolicyEngine(_DEFAULT_POLICY)
    findings = [{"severity": "high", "status": "open", "category": "authz"}]
    assert p.should_block_findings(findings, gate_name="ci_default")


def test_ci_gate_allows_non_matching_findings() -> None:
    p = PolicyEngine(_DEFAULT_POLICY)
    findings = [{"severity": "medium", "status": "open", "category": "config"}]
    assert not p.should_block_findings(findings, gate_name="ci_default")
