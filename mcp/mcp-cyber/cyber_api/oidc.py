"""OIDC bearer validation: JWKS + issuer + audience → TokenUser (RBAC from groups claim)."""

from __future__ import annotations

from typing import Any
from uuid import UUID

import jwt
import structlog
from jwt import PyJWKClient

from cyber_api.settings import settings
from cyber_api.token_user import TokenUser

log = structlog.get_logger()


def oidc_is_configured() -> bool:
    return bool(
        (settings.oidc_issuer or "").strip()
        and (settings.oidc_audience or "").strip()
        and (settings.oidc_jwks_url or "").strip()
    )


def _split_csv(s: str) -> set[str]:
    return {x.strip().lower() for x in (s or "").split(",") if x.strip()}


def _groups_from_payload(data: dict[str, Any], claim: str) -> list[str]:
    raw = data.get(claim)
    if raw is None:
        raw = data.get("roles")
    if raw is None:
        ra = data.get("realm_access")
        if isinstance(ra, dict):
            raw = ra.get("roles")
    if raw is None:
        return []
    if isinstance(raw, str):
        return [raw] if raw else []
    if isinstance(raw, list):
        return [str(x) for x in raw if x is not None]
    return []


def _role_from_groups(groups: list[str]) -> str:
    gl = [g.lower() for g in groups]
    admin_hits = _split_csv(settings.oidc_admin_groups)
    if any(g in admin_hits for g in gl):
        return "admin"
    sec_hits = _split_csv(settings.oidc_security_groups)
    if any(g in sec_hits for g in gl):
        return "security_engineer"
    mgr_hits = _split_csv(settings.oidc_manager_groups)
    if any(g in mgr_hits for g in gl):
        return "manager"
    return "developer"


def verify_oidc_token(token: str) -> TokenUser:
    """Validate JWT via JWKS; raise jwt.PyJWTError on failure."""
    if not oidc_is_configured():
        raise jwt.InvalidTokenError("OIDC is not configured")

    jwks_url = settings.oidc_jwks_url.strip()
    issuer = settings.oidc_issuer.strip()
    audience = settings.oidc_audience.strip()

    jwks_client = PyJWKClient(jwks_url)
    signing_key = jwks_client.get_signing_key_from_jwt(token)

    data = jwt.decode(
        token,
        signing_key.key,
        algorithms=["RS256", "ES256"],
        audience=audience,
        issuer=issuer,
        options={"require": ["exp"]},
    )

    sub = str(data.get("sub") or "")
    if not sub:
        raise jwt.InvalidTokenError("Token missing sub")

    email_claim = settings.oidc_email_claim or "email"
    email = str(data.get(email_claim) or f"{sub}@idp.unspecified")

    org_claim = settings.oidc_org_id_claim or "org_id"
    org_raw = data.get(org_claim)
    org_id: UUID | None = None
    if org_raw:
        try:
            org_id = UUID(str(org_raw))
        except ValueError as e:
            raise jwt.InvalidTokenError(f"Invalid {org_claim} UUID") from e
    elif (settings.oidc_default_org_id or "").strip():
        try:
            org_id = UUID(settings.oidc_default_org_id.strip())
        except ValueError as e:
            raise jwt.InvalidTokenError("CYBER_OIDC_DEFAULT_ORG_ID is not a valid UUID") from e
    else:
        raise jwt.InvalidTokenError(
            f"Token missing {org_claim}; set CYBER_OIDC_DEFAULT_ORG_ID or emit org claim"
        )

    groups_claim = settings.oidc_groups_claim or "groups"
    groups = _groups_from_payload(data, groups_claim)
    role = _role_from_groups(groups)

    log.info("oidc_token_accepted", sub=sub, role=role)
    return TokenUser(sub=sub, org_id=org_id, email=email, role=role)
