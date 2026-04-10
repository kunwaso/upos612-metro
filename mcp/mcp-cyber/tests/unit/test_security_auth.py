from __future__ import annotations

from types import SimpleNamespace

import jwt
import pytest
from fastapi import HTTPException


def _oidc_fail(*_args, **_kwargs):
    raise RuntimeError("oidc verification failed")


def test_decode_token_rejects_oidc_failure_without_dev_fallback(monkeypatch: pytest.MonkeyPatch) -> None:
    from cyber_api import security
    from cyber_api import oidc

    dev_secret = "unit-secret-abcdefghijklmnopqrstuvwxyz-123456"
    monkeypatch.setattr(
        security,
        "settings",
        SimpleNamespace(auth_allow_dev_jwt=True, auth_dev_jwt_alongside_oidc=False, dev_secret=dev_secret),
    )
    monkeypatch.setattr(oidc, "oidc_is_configured", lambda: True)
    monkeypatch.setattr(oidc, "verify_oidc_token", _oidc_fail)

    with pytest.raises(HTTPException) as ei:
        security.decode_token("bad-token")
    assert ei.value.status_code == 401


def test_decode_token_allows_dev_fallback_only_when_enabled(monkeypatch: pytest.MonkeyPatch) -> None:
    from cyber_api import security
    from cyber_api import oidc

    dev_secret = "unit-secret-abcdefghijklmnopqrstuvwxyz-123456"
    token = jwt.encode(
        {
            "sub": "00000000-0000-4000-8000-000000000099",
            "org_id": "00000000-0000-4000-8000-000000000042",
            "email": "dev@example.com",
            "role": "developer",
        },
        dev_secret,
        algorithm="HS256",
    )

    monkeypatch.setattr(
        security,
        "settings",
        SimpleNamespace(auth_allow_dev_jwt=True, auth_dev_jwt_alongside_oidc=True, dev_secret=dev_secret),
    )
    monkeypatch.setattr(oidc, "oidc_is_configured", lambda: True)
    monkeypatch.setattr(oidc, "verify_oidc_token", _oidc_fail)

    user = security.decode_token(token)
    assert user.email == "dev@example.com"
    assert str(user.org_id) == "00000000-0000-4000-8000-000000000042"


def test_decode_token_rejects_when_dev_jwt_disabled_and_no_oidc(monkeypatch: pytest.MonkeyPatch) -> None:
    from cyber_api import security
    from cyber_api import oidc

    dev_secret = "unit-secret-abcdefghijklmnopqrstuvwxyz-123456"
    monkeypatch.setattr(
        security,
        "settings",
        SimpleNamespace(auth_allow_dev_jwt=False, auth_dev_jwt_alongside_oidc=False, dev_secret=dev_secret),
    )
    monkeypatch.setattr(oidc, "oidc_is_configured", lambda: False)

    with pytest.raises(HTTPException) as ei:
        security.decode_token("any-token")
    assert ei.value.status_code == 401
