from __future__ import annotations

from typing import Annotated
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select

from cyber_api.audit import write_audit
from cyber_api.deps import DbSession
from cyber_api.schemas import ProjectCreate, ProjectOut
from cyber_api.principals import actor_uuid
from cyber_api.security import TokenUser, get_current_user
from cyber_db.models import Project

router = APIRouter(prefix="/v1/projects", tags=["projects"])


@router.post("", response_model=ProjectOut, status_code=status.HTTP_201_CREATED)
async def create_project(
    body: ProjectCreate,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    existing = await session.scalar(
        select(Project).where(Project.org_id == user.org_id, Project.slug == body.slug)
    )
    if existing:
        raise HTTPException(status.HTTP_409_CONFLICT, "Project slug exists")
    p = Project(org_id=user.org_id, slug=body.slug, name=body.name, owner_team=body.owner_team)
    session.add(p)
    await write_audit(
        session,
        actor_id=actor_uuid(user),
        action="project.create",
        object_type="project",
        object_id=str(p.id),
        payload={"slug": body.slug},
    )
    await session.commit()
    await session.refresh(p)
    return p


@router.get("", response_model=list[ProjectOut])
async def list_projects(
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    res = await session.execute(select(Project).where(Project.org_id == user.org_id))
    return list(res.scalars().all())
