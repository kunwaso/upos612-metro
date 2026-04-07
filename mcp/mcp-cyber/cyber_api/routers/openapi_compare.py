"""Compare two stored OpenAPI JSON artifacts (same org)."""

from __future__ import annotations

from pathlib import Path
from typing import Annotated
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, status

from cyber_api.deps import DbSession
from cyber_api.schemas import OpenAPIDiffOut
from cyber_api.security import TokenUser, get_current_user
from cyber_db.models import Environment, OpenAPIArtifact, Project
from cyber_reports.openapi_diff import diff_openapi_dicts, load_openapi_json

router = APIRouter(prefix="/v1", tags=["openapi"])


@router.get("/openapi-artifacts/{artifact_id_a}/diff/{artifact_id_b}", response_model=OpenAPIDiffOut)
async def diff_openapi_artifacts(
    artifact_id_a: UUID,
    artifact_id_b: UUID,
    session: DbSession,
    user: Annotated[TokenUser, Depends(get_current_user)],
):
    a = await session.get(OpenAPIArtifact, artifact_id_a)
    b = await session.get(OpenAPIArtifact, artifact_id_b)
    if not a or not b:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Artifact not found")

    env_a = await session.get(Environment, a.environment_id)
    env_b = await session.get(Environment, b.environment_id)
    if not env_a or not env_b:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Artifact not found")
    proj_a = await session.get(Project, env_a.project_id)
    proj_b = await session.get(Project, env_b.project_id)
    if (
        not proj_a
        or not proj_b
        or proj_a.org_id != user.org_id
        or proj_b.org_id != user.org_id
    ):
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Artifact not found")

    pa = Path(a.storage_uri)
    pb = Path(b.storage_uri)
    if not pa.is_file() or not pb.is_file():
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Artifact storage missing")

    try:
        doc_a = load_openapi_json(pa.read_text(encoding="utf-8"))
        doc_b = load_openapi_json(pb.read_text(encoding="utf-8"))
    except (OSError, UnicodeError, ValueError) as e:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, f"Invalid artifact: {e}") from e

    diff = diff_openapi_dicts(doc_a, doc_b)
    return OpenAPIDiffOut(
        artifact_id_a=artifact_id_a,
        artifact_id_b=artifact_id_b,
        sha256_a=a.sha256,
        sha256_b=b.sha256,
        diff=diff,
    )
