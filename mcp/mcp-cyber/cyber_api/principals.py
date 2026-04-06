from __future__ import annotations

from uuid import UUID

from cyber_api.security import TokenUser


def actor_uuid(user: TokenUser) -> UUID | None:
    try:
        return UUID(user.sub)
    except ValueError:
        return None
