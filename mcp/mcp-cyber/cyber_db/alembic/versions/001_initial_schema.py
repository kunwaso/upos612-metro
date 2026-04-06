"""initial schema

Revision ID: 001
Revises:
Create Date: 2026-04-06

"""

from typing import Sequence, Union

import sqlalchemy as sa
from alembic import op
from sqlalchemy.dialects import postgresql

revision: str = "001"
down_revision: Union[str, None] = None
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    op.create_table(
        "organizations",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("name", sa.Text(), nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_table(
        "users",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("org_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("email", sa.Text(), nullable=False),
        sa.Column("role", sa.String(length=32), nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["org_id"], ["organizations.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("email"),
    )
    op.create_table(
        "projects",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("org_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("slug", sa.Text(), nullable=False),
        sa.Column("name", sa.Text(), nullable=False),
        sa.Column("owner_team", sa.Text(), nullable=True),
        sa.ForeignKeyConstraint(["org_id"], ["organizations.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("org_id", "slug", name="uq_project_org_slug"),
    )
    op.create_table(
        "environments",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("project_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("name", sa.Text(), nullable=False),
        sa.Column("class", sa.String(length=16), nullable=False),
        sa.Column("base_url", sa.Text(), nullable=False),
        sa.Column("allowlist", postgresql.JSONB(astext_type=sa.Text()), nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["project_id"], ["projects.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_table(
        "assets",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("environment_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("kind", sa.String(length=16), nullable=False),
        sa.Column("tags", postgresql.ARRAY(sa.Text()), nullable=True),
        sa.Column("module", sa.Text(), nullable=True),
        sa.Column("route_pattern", sa.Text(), nullable=True),
        sa.ForeignKeyConstraint(["environment_id"], ["environments.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_table(
        "openapi_artifacts",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("environment_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("version", sa.Text(), nullable=True),
        sa.Column("storage_uri", sa.Text(), nullable=False),
        sa.Column("sha256", sa.Text(), nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["environment_id"], ["environments.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_table(
        "scan_profiles",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("environment_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("name", sa.Text(), nullable=False),
        sa.Column("mode", sa.String(length=32), nullable=False),
        sa.Column("adapter_ids", postgresql.ARRAY(sa.Text()), nullable=False),
        sa.Column("rate_limit_rps", sa.Numeric(8, 2), nullable=True),
        sa.Column("max_concurrency", sa.Integer(), nullable=True),
        sa.Column("credential_ref", sa.Text(), nullable=True),
        sa.Column("options", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
        sa.ForeignKeyConstraint(["environment_id"], ["environments.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_table(
        "approvals",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("profile_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("requester_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("approver_id", postgresql.UUID(as_uuid=True), nullable=True),
        sa.Column("status", sa.String(length=16), nullable=False),
        sa.Column("reason", sa.Text(), nullable=True),
        sa.Column("payload_tier", sa.String(length=16), nullable=False),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("expires_at", sa.DateTime(timezone=True), nullable=False),
        sa.ForeignKeyConstraint(["approver_id"], ["users.id"]),
        sa.ForeignKeyConstraint(["profile_id"], ["scan_profiles.id"]),
        sa.ForeignKeyConstraint(["requester_id"], ["users.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_table(
        "scan_runs",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("profile_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("started_by", postgresql.UUID(as_uuid=True), nullable=True),
        sa.Column("status", sa.String(length=16), nullable=False),
        sa.Column("started_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("finished_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("baseline_scan_id", postgresql.UUID(as_uuid=True), nullable=True),
        sa.Column("summary", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
        sa.Column("trace_id", sa.Text(), nullable=False),
        sa.Column("idempotency_key", sa.String(length=128), nullable=True),
        sa.ForeignKeyConstraint(["baseline_scan_id"], ["scan_runs.id"]),
        sa.ForeignKeyConstraint(["profile_id"], ["scan_profiles.id"]),
        sa.ForeignKeyConstraint(["started_by"], ["users.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("idempotency_key"),
    )
    op.create_table(
        "scan_events",
        sa.Column("id", sa.BigInteger(), autoincrement=True, nullable=False),
        sa.Column("scan_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("ts", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("level", sa.String(length=16), nullable=False),
        sa.Column("adapter", sa.Text(), nullable=False),
        sa.Column("event_type", sa.Text(), nullable=False),
        sa.Column("message", sa.Text(), nullable=True),
        sa.Column("context", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
        sa.ForeignKeyConstraint(["scan_id"], ["scan_runs.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_table(
        "findings",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("scan_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("project_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("environment_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("rule_id", sa.Text(), nullable=False),
        sa.Column("category", sa.Text(), nullable=False),
        sa.Column("title", sa.Text(), nullable=False),
        sa.Column("severity", sa.String(length=16), nullable=False),
        sa.Column("confidence", sa.Numeric(5, 4), nullable=False),
        sa.Column("cvss_score", sa.Numeric(4, 1), nullable=True),
        sa.Column("status", sa.String(length=24), nullable=False, server_default="open"),
        sa.Column("affected_asset", sa.Text(), nullable=True),
        sa.Column("url", sa.Text(), nullable=True),
        sa.Column("component", sa.Text(), nullable=True),
        sa.Column("parameter", sa.Text(), nullable=True),
        sa.Column("fingerprint", sa.Text(), nullable=False),
        sa.Column("evidence", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
        sa.Column("reproduction", sa.Text(), nullable=True),
        sa.Column("root_cause", sa.Text(), nullable=True),
        sa.Column("remediation", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
        sa.Column("references", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
        sa.Column("first_seen_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("last_seen_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("fixed_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("owner_team", sa.Text(), nullable=True),
        sa.Column("tags", postgresql.ARRAY(sa.Text()), nullable=True),
        sa.ForeignKeyConstraint(["environment_id"], ["environments.id"]),
        sa.ForeignKeyConstraint(["project_id"], ["projects.id"]),
        sa.ForeignKeyConstraint(["scan_id"], ["scan_runs.id"]),
        sa.PrimaryKeyConstraint("id"),
        sa.UniqueConstraint("scan_id", "fingerprint", name="uq_finding_scan_fingerprint"),
    )
    op.create_table(
        "suppressions",
        sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("project_id", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("fingerprint", sa.Text(), nullable=False),
        sa.Column("reason", sa.Text(), nullable=False),
        sa.Column("created_by", postgresql.UUID(as_uuid=True), nullable=False),
        sa.Column("expires_at", sa.DateTime(timezone=True), nullable=True),
        sa.Column("created_at", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.ForeignKeyConstraint(["created_by"], ["users.id"]),
        sa.ForeignKeyConstraint(["project_id"], ["projects.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_table(
        "audit_log",
        sa.Column("id", sa.BigInteger(), autoincrement=True, nullable=False),
        sa.Column("ts", sa.DateTime(timezone=True), server_default=sa.text("now()"), nullable=False),
        sa.Column("actor_id", postgresql.UUID(as_uuid=True), nullable=True),
        sa.Column("action", sa.Text(), nullable=False),
        sa.Column("object_type", sa.Text(), nullable=False),
        sa.Column("object_id", sa.Text(), nullable=False),
        sa.Column("payload", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
        sa.ForeignKeyConstraint(["actor_id"], ["users.id"]),
        sa.PrimaryKeyConstraint("id"),
    )
    op.create_index("idx_findings_project_status", "findings", ["project_id", "status"])
    op.create_index("idx_findings_fingerprint", "findings", ["fingerprint"])
    op.create_index("idx_scan_events_scan", "scan_events", ["scan_id"])


def downgrade() -> None:
    op.drop_index("idx_scan_events_scan")
    op.drop_index("idx_findings_fingerprint")
    op.drop_index("idx_findings_project_status")
    op.drop_table("audit_log")
    op.drop_table("suppressions")
    op.drop_table("findings")
    op.drop_table("scan_events")
    op.drop_table("scan_runs")
    op.drop_table("approvals")
    op.drop_table("scan_profiles")
    op.drop_table("openapi_artifacts")
    op.drop_table("assets")
    op.drop_table("environments")
    op.drop_table("projects")
    op.drop_table("users")
    op.drop_table("organizations")
