from __future__ import annotations

import uuid
from typing import Any

from sqlalchemy.ext.asyncio import AsyncSession

from cyber_db.models import AuditLog


async def write_audit(
    session: AsyncSession,
    *,
    actor_id: uuid.UUID | None,
    action: str,
    object_type: str,
    object_id: str,
    payload: dict[str, Any] | None = None,
) -> None:
    session.add(
        AuditLog(
            actor_id=actor_id,
            action=action,
            object_type=object_type,
            object_id=object_id,
            payload=payload or {},
        )
    )
