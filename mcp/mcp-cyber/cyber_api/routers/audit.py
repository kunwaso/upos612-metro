from __future__ import annotations

from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select

from cyber_api.deps import DbSession
from cyber_api.security import TokenUser, get_current_user
from cyber_db.models import AuditLog

router = APIRouter(prefix="/v1/audit-log", tags=["audit"])


@router.get("")
async def list_audit_log(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
    limit: int = 100,
):
    if user.role not in ("security_engineer", "admin"):
        raise HTTPException(status.HTTP_403_FORBIDDEN, "Insufficient role")
    stmt = select(AuditLog).order_by(AuditLog.ts.desc()).limit(min(limit, 500))
    res = await session.execute(stmt)
    rows = res.scalars().all()
    out = []
    for r in rows:
        payload = dict(r.payload or {})
        for k in list(payload.keys()):
            if "token" in k.lower() or "secret" in k.lower():
                payload[k] = "[redacted]"
        out.append(
            {
                "id": r.id,
                "ts": r.ts.isoformat(),
                "actor_id": str(r.actor_id) if r.actor_id else None,
                "action": r.action,
                "object_type": r.object_type,
                "object_id": r.object_id,
                "payload": payload,
            }
        )
    return out
