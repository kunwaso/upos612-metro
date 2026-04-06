from __future__ import annotations

from datetime import datetime, timedelta, timezone
from types import SimpleNamespace
from unittest.mock import patch

import jwt
import pytest
from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.primitives.asymmetric import rsa


def _rsa_pem_pair() -> tuple[bytes, bytes]:
    key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
    priv = key.private_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PrivateFormat.PKCS8,
        encryption_algorithm=serialization.NoEncryption(),
    )
    pub = key.public_key().public_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PublicFormat.SubjectPublicKeyInfo,
    )
    return priv, pub


def _fake_settings() -> SimpleNamespace:
    return SimpleNamespace(
        oidc_issuer="https://issuer.example/",
        oidc_audience="cyber-api",
        oidc_jwks_url="https://issuer.example/jwks",
        oidc_default_org_id="00000000-0000-4000-8000-000000000042",
        oidc_email_claim="email",
        oidc_org_id_claim="org_id",
        oidc_groups_claim="groups",
        oidc_admin_groups="admin,cyber-admin",
        oidc_security_groups="security,sec-eng",
        oidc_manager_groups="manager",
    )


def test_verify_oidc_maps_admin_role(monkeypatch: pytest.MonkeyPatch) -> None:
    from cyber_api import oidc

    priv, pub = _rsa_pem_pair()
    now = datetime.now(timezone.utc)
    payload = {
        "sub": "user-1",
        "email": "u@example.com",
        "groups": ["cyber-admin"],
        "aud": "cyber-api",
        "iss": "https://issuer.example/",
        "exp": now + timedelta(hours=1),
    }
    token = jwt.encode(payload, priv, algorithm="RS256")

    monkeypatch.setattr(oidc, "settings", _fake_settings())

    class _Key:
        def __init__(self, k: bytes) -> None:
            self.key = k

    with patch.object(oidc.PyJWKClient, "get_signing_key_from_jwt", lambda self, t: _Key(pub)):
        user = oidc.verify_oidc_token(token)

    assert user.sub == "user-1"
    assert user.email == "u@example.com"
    assert user.role == "admin"
    assert str(user.org_id) == "00000000-0000-4000-8000-000000000042"


def test_role_from_groups_security(monkeypatch: pytest.MonkeyPatch) -> None:
    from cyber_api import oidc

    monkeypatch.setattr(oidc, "settings", _fake_settings())
    assert oidc._role_from_groups(["sec-eng"]) == "security_engineer"
    assert oidc._role_from_groups(["random"]) == "developer"
