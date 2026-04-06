from enum import Enum
from typing import Any
from uuid import UUID

from pydantic import BaseModel, Field


class EnvironmentClass(str, Enum):
    LOCAL = "local"
    DEV = "dev"
    STAGING = "staging"
    UAT = "uat"
    PROD = "prod"


class ScanMode(str, Enum):
    PASSIVE = "passive"
    AUTHENTICATED_PASSIVE = "authenticated_passive"
    ACTIVE_CONTROLLED = "active_controlled"


class ScanRunCreate(BaseModel):
    profile_id: UUID
    target_urls: list[str] | None = None
    openapi_artifact_id: UUID | None = None
    baseline_scan_id: UUID | None = None
    idempotency_key: str | None = Field(default=None, max_length=128)
    note: str | None = Field(default=None, max_length=500)


class AllowlistConfig(BaseModel):
    """JSON stored on environments.allowlist."""

    hosts: list[str] = Field(default_factory=list)
    path_prefixes: list[str] = Field(default_factory=list)
    methods: list[str] = Field(default_factory=lambda: ["GET", "HEAD", "POST", "PUT", "PATCH", "DELETE"])


class ScanContextDTO(BaseModel):
    """Serializable scan context for engine (from DB rows)."""

    scan_id: str
    trace_id: str
    profile_id: str
    mode: str
    adapter_ids: list[str]
    rate_limit_rps: float
    max_concurrency: int
    environment_id: str
    environment_name: str
    environment_class: str
    base_url: str
    allowlist: dict[str, Any]
    project_id: str
    options: dict[str, Any] = Field(default_factory=dict)
    approval_id: str | None = None
