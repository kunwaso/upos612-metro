"""Resolve API principals to persisted ``users.id`` (approvals, suppressions FK)."""

from __future__ import annotations

import uuid

from fastapi import HTTPException, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from cyber_api.token_user import TokenUser
from cyber_db.models import User


async def require_db_user_id(session: AsyncSession, user: TokenUser) -> uuid.UUID:
    """Return users.id for this org + email, or 400 if no row (run seed / user sync)."""
    row = await session.scalar(select(User.id).where(User.email == user.email, User.org_id == user.org_id))
    if row is None:
        raise HTTPException(
            status.HTTP_400_BAD_REQUEST,
            "No users row for this email in your org; create the user (e.g. scripts/seed_demo.py) or sync from IdP.",
        )
    return row
