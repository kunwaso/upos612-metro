"""Quick schema/DB model sanity check."""
import sys, os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from cyber_db.models import (
    Organization, User, Project, Environment, Asset, OpenAPIArtifact, RoutesArtifact,
    ScanProfile, Approval, ScanRun, ScanEvent, Finding, Suppression, AuditLog
)

plan_tables = [
    "organizations", "users", "projects", "environments", "assets",
    "openapi_artifacts", "routes_artifacts", "scan_profiles", "approvals", "scan_runs",
    "scan_events", "findings", "suppressions", "audit_log",
]
actual = [
    Organization.__tablename__, User.__tablename__, Project.__tablename__,
    Environment.__tablename__, Asset.__tablename__, OpenAPIArtifact.__tablename__,
    RoutesArtifact.__tablename__, ScanProfile.__tablename__, Approval.__tablename__, ScanRun.__tablename__,
    ScanEvent.__tablename__, Finding.__tablename__, Suppression.__tablename__,
    AuditLog.__tablename__,
]

print("Plan tables vs DB models:")
for t in plan_tables:
    status = "OK" if t in actual else "MISSING"
    print(f"  {status}: {t}")

finding_constraints = [c.name for c in Finding.__table__.constraints]
print("Finding constraints:", finding_constraints)

sr_cols = [c.name for c in ScanRun.__table__.columns]
print("ScanRun.idempotency_key:", "idempotency_key" in sr_cols)
print("ScanRun.options:", "options" in sr_cols)

f_cols = [c.name for c in Finding.__table__.columns]
print("Finding.status default:", Finding.__table__.c.status.server_default)
print("Finding.fingerprint present:", "fingerprint" in f_cols)
print("Finding.external_refs (references column):", "references" in f_cols)
