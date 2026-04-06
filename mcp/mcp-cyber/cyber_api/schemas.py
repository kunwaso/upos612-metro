from __future__ import annotations

from datetime import datetime
from typing import Any
from uuid import UUID

from pydantic import BaseModel, Field, field_validator


class ProjectCreate(BaseModel):
    slug: str
    name: str
    owner_team: str | None = None


class ProjectOut(BaseModel):
    id: UUID
    org_id: UUID
    slug: str
    name: str
    owner_team: str | None

    model_config = {"from_attributes": True}


class EnvironmentCreate(BaseModel):
    name: str
    env_class: str = Field(..., pattern="^(local|dev|staging|uat|prod)$")
    base_url: str
    allowlist: dict[str, Any] = Field(default_factory=dict)


class EnvironmentOut(BaseModel):
    id: UUID
    project_id: UUID
    name: str
    env_class: str
    base_url: str
    allowlist: dict[str, Any]

    model_config = {"from_attributes": True}


class ScanProfileCreate(BaseModel):
    name: str
    mode: str = Field(..., pattern="^(passive|authenticated_passive|active_controlled)$")
    adapter_ids: list[str]
    rate_limit_rps: float = 2.0
    max_concurrency: int = 3
    credential_ref: str | None = None
    options: dict[str, Any] = Field(default_factory=dict)


class ScanProfileOut(BaseModel):
    id: UUID
    environment_id: UUID
    name: str
    mode: str
    adapter_ids: list[str]
    rate_limit_rps: float
    max_concurrency: int
    credential_ref: str | None
    options: dict[str, Any]

    model_config = {"from_attributes": True}

    @field_validator("rate_limit_rps", mode="before")
    @classmethod
    def _coerce_rate(cls, v: Any) -> float:
        return float(v) if v is not None else 0.0


class ScanCreate(BaseModel):
    profile_id: UUID
    target_urls: list[str] | None = None
    openapi_artifact_id: UUID | None = None
    baseline_scan_id: UUID | None = None
    idempotency_key: str | None = Field(default=None, max_length=128)
    approval_id: UUID | None = None
    note: str | None = Field(default=None, max_length=500)


class ScanOut(BaseModel):
    id: UUID
    profile_id: UUID
    status: str
    started_at: datetime
    finished_at: datetime | None
    trace_id: str
    summary: dict[str, Any] | None

    model_config = {"from_attributes": True}


class FindingOut(BaseModel):
    finding_id: UUID
    scan_id: UUID
    project_id: UUID
    environment_id: UUID
    rule_id: str
    category: str
    title: str
    severity: str
    confidence: float
    cvss_score: float | None
    status: str
    affected_asset: str | None
    url: str | None
    component: str | None
    parameter: str | None
    fingerprint: str
    evidence: list[Any]
    reproduction: str | None
    root_cause: str | None
    remediation: dict[str, Any]
    references: list[Any] = Field(default_factory=list)
    first_seen_at: datetime | None = None
    last_seen_at: datetime | None = None
    fixed_at: datetime | None = None
    owner_team: str | None = None
    tags: list[str] = Field(default_factory=list)

    model_config = {"from_attributes": True}

    @classmethod
    def from_orm_finding(cls, f: Any) -> "FindingOut":
        return cls(
            finding_id=f.id,
            scan_id=f.scan_id,
            project_id=f.project_id,
            environment_id=f.environment_id,
            rule_id=f.rule_id,
            category=f.category,
            title=f.title,
            severity=f.severity,
            confidence=float(f.confidence),
            cvss_score=float(f.cvss_score) if f.cvss_score is not None else None,
            status=f.status,
            affected_asset=f.affected_asset,
            url=f.url,
            component=f.component,
            parameter=f.parameter,
            fingerprint=f.fingerprint,
            evidence=f.evidence or [],
            reproduction=f.reproduction,
            root_cause=f.root_cause,
            remediation=f.remediation or {},
            references=f.external_refs or [],
            first_seen_at=f.first_seen_at,
            last_seen_at=f.last_seen_at,
            fixed_at=f.fixed_at,
            owner_team=f.owner_team,
            tags=list(f.tags or []),
        )


class FindingTransition(BaseModel):
    status: str
    reason: str | None = None


class OpenAPIImport(BaseModel):
    version: str | None = None
    spec_json: str


class CompareOut(BaseModel):
    scan_id_a: UUID
    scan_id_b: UUID
    new_fingerprints: list[str]
    resolved_fingerprints: list[str]
    unchanged_count: int
