"""Shared auth principal (dev JWT + OIDC)."""

from __future__ import annotations

from uuid import UUID

from pydantic import BaseModel


class TokenUser(BaseModel):
    sub: str
    org_id: UUID
    email: str
    role: str
