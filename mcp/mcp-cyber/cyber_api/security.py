"""Bearer auth: OIDC (JWKS) when configured, else HS256 dev JWT."""

from __future__ import annotations

import os
from datetime import datetime, timedelta, timezone
from typing import Annotated, Any
from uuid import UUID

import jwt
from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer
from cyber_api.settings import settings
from cyber_api.token_user import TokenUser

security = HTTPBearer(auto_error=False)


def create_dev_token(
    *,
    user_id: str,
    org_id: UUID,
    email: str,
    role: str = "developer",
    ttl_hours: int = 24,
) -> str:
    secret = os.environ.get("CYBER_DEV_SECRET", settings.dev_secret)
    now = datetime.now(timezone.utc)
    payload: dict[str, Any] = {
        "sub": user_id,
        "org_id": str(org_id),
        "email": email,
        "role": role,
        "iat": now,
        "exp": now + timedelta(hours=ttl_hours),
    }
    return jwt.encode(payload, secret, algorithm="HS256")


def decode_dev_jwt(token: str) -> TokenUser:
    secret = os.environ.get("CYBER_DEV_SECRET", settings.dev_secret)
    try:
        data = jwt.decode(token, secret, algorithms=["HS256"])
        return TokenUser(
            sub=data["sub"],
            org_id=UUID(data["org_id"]),
            email=data["email"],
            role=data.get("role", "developer"),
        )
    except Exception as e:
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Invalid dev token") from e


def decode_token(token: str) -> TokenUser:
    """Try OIDC (JWKS) when configured, then optional dev HS256 JWT."""
    from cyber_api import oidc

    if oidc.oidc_is_configured():
        try:
            return oidc.verify_oidc_token(token)
        except Exception:
            if not settings.auth_dev_jwt_alongside_oidc:
                raise HTTPException(
                    status.HTTP_401_UNAUTHORIZED,
                    "Invalid or untrusted bearer token (OIDC)",
                ) from None
    return decode_dev_jwt(token)


async def get_current_user(
    cred: Annotated[HTTPAuthorizationCredentials | None, Depends(security)],
) -> TokenUser:
    if cred is None or cred.scheme.lower() != "bearer":
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Bearer token required")
    return decode_token(cred.credentials)


def require_roles(*roles: str):
    async def _inner(user: Annotated[TokenUser, Depends(get_current_user)]) -> TokenUser:
        if user.role not in roles and user.role != "admin":
            raise HTTPException(status.HTTP_403_FORBIDDEN, "Insufficient role")
        return user

    return _inner
