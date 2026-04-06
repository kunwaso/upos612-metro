"""Skip persisting findings that match an active suppression (project + fingerprint)."""

from __future__ import annotations

import uuid
from datetime import datetime, timezone

from sqlalchemy import or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from cyber_db.models import Suppression


async def is_fingerprint_suppressed(
    session: AsyncSession,
    project_id: uuid.UUID,
    fingerprint: str,
) -> bool:
    now = datetime.now(timezone.utc)
    q = (
        select(Suppression.id)
        .where(
            Suppression.project_id == project_id,
            Suppression.fingerprint == fingerprint,
            or_(Suppression.expires_at.is_(None), Suppression.expires_at > now),
        )
        .limit(1)
    )
    return (await session.execute(q)).scalar_one_or_none() is not None
