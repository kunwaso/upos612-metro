from __future__ import annotations

from typing import Any
from uuid import UUID

from pydantic import BaseModel, Field


class RemediationBlock(BaseModel):
    summary: str = ""
    steps: list[str] = Field(default_factory=list)


class RawFinding(BaseModel):
    """Adapter output before normalization."""

    rule_id: str
    category: str
    title: str
    severity: str
    confidence: float = Field(ge=0.0, le=1.0)
    cvss_score: float | None = None
    affected_asset: str | None = None
    url: str | None = None
    component: str | None = None
    parameter: str | None = None
    evidence: list[dict[str, Any]] = Field(default_factory=list)
    reproduction: str | None = None
    root_cause: str | None = None
    remediation: RemediationBlock = Field(default_factory=RemediationBlock)
    references: list[dict[str, str]] = Field(default_factory=list)
    tags: list[str] = Field(default_factory=list)


class FindingOut(BaseModel):
    """API / MCP normalized finding."""

    finding_id: UUID
    scan_id: UUID
    project_id: UUID
    environment_id: UUID
    environment_name: str | None = None
    rule_id: str
    category: str
    title: str
    severity: str
    confidence: float
    cvss_score: float | None = None
    status: str
    affected_asset: str | None = None
    url: str | None = None
    component: str | None = None
    parameter: str | None = None
    evidence: list[dict[str, Any]] = Field(default_factory=list)
    reproduction: str | None = None
    root_cause: str | None = None
    remediation: dict[str, Any] = Field(default_factory=dict)
    references: list[dict[str, str]] = Field(default_factory=list)
    first_seen_at: str | None = None
    last_seen_at: str | None = None
    fixed_at: str | None = None
    owner_team: str | None = None
    tags: list[str] = Field(default_factory=list)
    fingerprint: str

    model_config = {"from_attributes": True}
