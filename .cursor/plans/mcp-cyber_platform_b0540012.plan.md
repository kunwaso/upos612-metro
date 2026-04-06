---
name: mcp-cyber platform
overview: "Design and starter blueprint for **mcp-cyber**: a defensive, allowlist-gated security validation platform with a Python scan engine, FastAPI control plane, PostgreSQL persistence, optional Redis-backed jobs, structured logging/SARIF-friendly outputs, and an MCP server (Python) for AI orchestration—scoped strictly to authorized assets and safe-by-default scan modes."
todos:
  - id: bootstrap-monorepo
    content: "Create mcp-cyber monorepo skeleton (pyproject, packages: cyber_core, cyber_db, cyber_engine, cyber_api, cyber_mcp, cyber_reports), Docker Compose, Alembic baseline"
    status: completed
  - id: policy-allowlist
    content: Implement PolicyEngine + allowlist enforcement + tests (prod passive-only, active requires approval)
    status: completed
  - id: passive-adapters
    content: Implement httpx-based passive adapters (headers/cookies, CORS/CSP/TLS basic) + Normalizer + DB persistence
    status: completed
  - id: api-scans
    content: FastAPI CRUD for projects/envs/profiles/scans + scan execution + compare_runs + OpenAPI export
    status: completed
  - id: mcp-tools
    content: "Python MCP server wrapping API (auth token, core tools: run_passive_scan, list_findings, export_report)"
    status: completed
  - id: reporting-ci
    content: Markdown/JSON/SARIF exporters + sample GitHub Action + remediation checklist generator
    status: completed
  - id: auth-browser-phase2
    content: "Phase 2: OIDC RBAC, Playwright authenticated adapter, vault credential refs, object storage for evidence"
    status: completed
isProject: false
---

# mcp-cyber — Enterprise defensive security validation platform

## 1. Executive summary

**mcp-cyber** is an internal **security posture validation** system: it registers **owned** web assets and environments, runs **layered, policy-gated** checks (passive by default), normalizes results into a **single finding model**, stores **immutable scan runs** with evidence, diffs against baselines, and exposes operations via **REST (OpenAPI)** and **MCP tools** so developers and AI assistants can run scans, triage, remediate, and retest in a closed loop.

**Non-goals (explicit):** attacking third parties, stealth/evasion, credential harvesting, phishing, malware, destructive exploitation, or unbounded internet scanning. **Active** and **authenticated-intrusive** paths require **allowlists**, **environment class**, **approval tokens**, **rate limits**, and **audit trails**.

**Remediation / AI (safety and compliance default):** mcp-cyber **does not autofix** application code, configuration, or infrastructure. **All fixes are review-required**: humans (or an IDE agent **under developer control**) apply changes via normal change management (PR, CI, release). The platform and MCP tools **detect, explain, prioritize, export evidence, suggest remediation text, and trigger retests**—they **do not** silently commit, merge, or mutate customer systems. Optional **future** “limited autofix” integrations (e.g. bot-opened PRs for narrow, low-risk change types) are **out of MVP** and must be **explicitly policy-gated**, audited, and separate from core scan workers.

**Integration note (your UPOS repo):** You already have [`mcp/audit-web-mcp`](mcp/audit-web-mcp) (Playwright). mcp-cyber should treat that as an **optional adapter** (`adapters/playwright_audit.py`) that forwards **scoped** URLs and receives structured artifacts—without duplicating “browser audit” logic in the MCP layer.

---

## 2. Recommended architecture (with rationale)

```mermaid
flowchart TB
  subgraph clients [Clients]
    AI[MCP clients Cursor etc]
    UI[Web UI Next.js]
    CI[CI pipelines]
  end

  subgraph control [Control plane]
    MCP[mcp_server Python MCP]
    API[FastAPI OpenAPI]
    Auth[OIDC SSO RBAC]
    Policy[Policy engine]
  end

  subgraph data [Data plane]
    PG[(PostgreSQL)]
    Obj[(Evidence object store S3 compatible)]
    Redis[(Redis optional)]
  end

  subgraph workers [Scan workers]
    Orch[Orchestrator]
    Adapters[Scanner adapters]
    Norm[Finding normalizer]
    Rep[Report generator]
  end

  AI --> MCP
  UI --> API
  CI --> API
  MCP --> API
  API --> Policy
  API --> PG
  API --> Redis
  API --> Orch
  Orch --> Adapters
  Adapters --> Norm
  Norm --> PG
  Norm --> Obj
  Rep --> PG
  Rep --> Obj
```

**Rationale**

| Choice | Why |
|--------|-----|
| **Python MCP server** | Same language as scan engine, one virtualenv, shared Pydantic models between API, workers, and MCP; official `mcp` SDK is mature; avoids maintaining parallel TS types for findings. |
| **FastAPI** | OpenAPI-first, async, fits PostgreSQL + background tasks; easy JWT/OIDC middleware. |
| **PostgreSQL** | ACID for findings/workflows; JSONB for evidence metadata; great for audit and trends. |
| **Redis (Phase 2+)** | Job queue (Celery/RQ), distributed rate limits, scan locks, circuit-breaker state—skip for single-node MVP if you use FastAPI `BackgroundTasks` + DB job rows. |
| **Object store for evidence** | Keeps DB lean; encrypt at rest; retention policies per tenant. |

**Boundaries**

- **Orchestrator**: DAG of checks, concurrency, timeouts, checkpoints, idempotent `scan_run` steps.
- **Adapters**: HTTP passive (`httpx`), TLS info (safe probes), OpenAPI diff, Playwright session (optional), custom “business rules” hooks.
- **Normalizer**: maps every adapter output → `Finding` + `EvidenceRef` + `Fingerprint`.
- **Policy engine**: answers “may this run?” from `environment_class`, `allowlist`, `scan_profile`, `approval_id`, role, and global kill-switch.

---

## 3. Monorepo folder structure

```
mcp-cyber/
  pyproject.toml                 # uv/poetry; shared workspace optional
  README.md
  docker-compose.yml
  deploy/
    k8s/                         # manifests, helm chart skeleton
    terraform/                   # optional
  configs/
    default.policy.yaml
    logging.json
  packages/
    cyber_core/                  # shared models, fingerprints, constants
      cyber_core/
        models/                  # pydantic v2 models
        policies/
        logging.py
    cyber_db/                    # SQLAlchemy 2.x + Alembic
      cyber_db/
        models.py
        session.py
      alembic/
    cyber_engine/                # orchestrator + adapters + checks
      cyber_engine/
        orchestrator.py
        adapters/
          base.py
          http_passive.py
          headers_cookies.py
          openapi_lint.py
          rbac_matrix.py         # consumes route matrix JSON from app CI
          playwright_session.py  # thin wrapper to existing audit-web or native PW
        normalizer.py
        policy_engine.py
        rate_limit.py
    cyber_api/                   # FastAPI app
      cyber_api/
        main.py
        routers/
        deps.py
        security.py
    cyber_worker/                # Celery or RQ entry (optional MVP: in-process)
      cyber_worker/
        tasks.py
    cyber_mcp/                   # MCP server: thin HTTP client to API + local read-only cache
      cyber_mcp/
        server.py
        tools/
    cyber_reports/               # SARIF, MD, HTML templates
      cyber_reports/
        sarif.py
        markdown.py
    cyber_ui/                    # Next.js or Vite+React (Phase 2)
      ...
  tests/
    unit/
    integration/
    security/                    # tests of the platform itself
  scripts/
    seed_demo.py
```

---

## 4. Component-by-component design (concise)

| Component | Responsibility |
|-----------|----------------|
| **Asset registry** | `Project`, `Environment`, `Asset` (base URL, tags), `AllowlistRule` (host/path/method), owners/teams. |
| **Scan profiles** | Passive-only vs authenticated-passive vs controlled-active; which adapters; max QPS; timeouts; credential vault refs. |
| **Orchestrator** | Creates `scan_run`, expands targets from OpenAPI + seed URLs, runs adapters with shared `ScanContext`, persists per-check `scan_event`, handles retry/resume via step checkpoints. |
| **Policy engine** | Central gate: `assert_scan_permitted(context)` before network I/O. |
| **Normalizer** | Dedup via `fingerprint = sha256(rule_id + normalized_url + param + variant)`. |
| **Evidence store** | Store HAR/screenshot/redacted response snippets in object storage; DB holds pointer + hash. |
| **Reporting** | Executive summary, developer checklist, SARIF 2.1.0, diff vs baseline. |
| **MCP layer** | Stateless tools that call API with service user token; never embed secrets in prompts; **no autofix**—orchestration and read/write of **findings and scan metadata only**, not repo or runtime mutation. |

---

## 5. Threat model (platform itself) — STRIDE sketch

| Threat | Mitigation |
|--------|------------|
| **Spoofing** | OIDC SSO, service accounts for MCP/CI, mTLS optional internal. |
| **Tampering** | Immutable `scan_run` rows; append-only `audit_log`; signed URLs for evidence download. |
| **Repudiation** | Structured audit on approvals, scan start/end, finding status changes. |
| **Information disclosure** | RBAC; project isolation; encrypt evidence; redact tokens in logs; least-privilege DB roles. |
| **Denial of service** | Rate limits per project/env; circuit breakers; max concurrent scans; worker caps. |
| **Elevation of privilege** | RBAC roles (`developer`, `security_engineer`, `manager`, `admin`); approval workflow for active scans. |

**High-risk component:** stored **test credentials** → use vault (HashiCorp Vault / cloud SM) with **reference IDs** only in DB; workers fetch short-lived secrets.

---

## 6. MCP server design

### AI and remediation policy (review-required; no platform autofix)

- **Default:** **Review-required remediation beats autofix.** Compliance- and safety-first posture: the system proves *what* is wrong and *how to verify* a fix; applying fixes stays **human-gated** (or developer-controlled IDE/PR flow).
- **MCP + AI role:** orchestrate scans (`run_*_scan`), query findings, summarize posture, export reports, update **finding workflow status** (`mark_finding_status`), build **checklists** (`create_remediation_plan`), and **retest** (`validate_fix`). These tools **must not** apply patches to repositories, containers, or cloud resources.
- **Trust boundary:** **Scans run in workers** behind the API/policy engine. The MCP server is a **thin client** to the API (plus optional read-only policy resources)—not a second scan engine that bypasses allowlists.
- **Optional later (non-MVP):** “Proposed patch” or bot-PR features live **outside** the scan execution path, with **explicit RBAC**, audit, and **no merge without review**—document as a separate integration if adopted.

**Tools (all require API auth + RBAC):** implement as wrappers over FastAPI.

For each tool below: **purpose**, **permission**, **safety**, **failure cases** are summarized; full JSON Schema can live in `packages/cyber_mcp/tools/schemas/*.json` and Pydantic mirrors in `cyber_core`.

| Tool | Purpose | Key inputs | Key outputs | Permission | Safety |
|------|---------|------------|-------------|------------|--------|
| `register_project` | Create project + owner | name, slug, team_id | project_id | `admin` or `security_engineer` | Org boundary |
| `register_environment` | Register env + class | project_id, class, base_url, allowlist | env_id | same | **Reject** if base_url not under allowlist registry |
| `create_scan_profile` | Define adapters + limits | env_id, mode, adapter_ids, rate_limit | profile_id | `security_engineer` | Passive-only for `prod` |
| `run_passive_scan` | Start passive run | profile_id, target_urls?, openapi_id? | scan_id | `developer+` | Hard allowlist |
| `run_authenticated_scan` | Session-based passive | profile_id, role_account_map_ref | scan_id | `security_engineer` | Requires approval if intrusive flags |
| `run_controlled_active_scan` | Limited active | profile_id, approval_id, payload_tier=`safe` | scan_id | `security_engineer` + approval | **Blocked** on prod without `PASSIVE_ONLY` override false + dual control |
| `import_openapi_spec` | Ingest OpenAPI | env_id, spec_url or content | openapi_artifact_id | `developer+` | URL must match allowlist |
| `compare_scan_runs` | Diff findings | scan_id_a, scan_id_b | diff_summary + new/resolved | `developer+` | Same project |
| `list_findings` | Query | filters (severity, status, tag) | finding[] | `developer+` | Row-level by project |
| `get_finding_detail` | One finding | finding_id | Finding + evidence | `developer+` | — |
| `export_report` | Export | scan_id, format (`json`,`md`,`sarif`,`html`) | uri or inline | `developer+` | Signed URL TTL |
| `summarize_security_posture` | Exec + top risks | project_id, env_id | summary object | `manager+` | Aggregated only |
| `mark_finding_status` | Workflow | finding_id, status, reason | updated | `developer+` / accept-risk needs `security_engineer` | Audit |
| `create_remediation_plan` | Generate checklist | scan_id | plan_id, tasks[] | `developer+` | — |
| `validate_fix` | Retest fingerprint | finding_id, scan_profile_id | pass/fail | `developer+` | Same checks only |
| `get_audit_log` | Compliance view | filters | events[] | `security_engineer+` | Sensitive fields masked |

**Resources / prompts:** `resource://cyber/policy/{project_id}`, `prompt://remediation/finding` (template pulling OWASP refs, no secrets).

**Guardrails in MCP process env:** `CYBER_API_URL`, `CYBER_API_TOKEN` (scoped), `CYBER_DEFAULT_PROJECT` optional; **refuse** if `CYBER_REQUIRE_ALLOWLIST=true` and target not registered.

---

## 7. Scanning engine (defensive-only)

**Adapter interface** (`packages/cyber_engine/cyber_engine/adapters/base.py`):

- `id`, `version`, `supports_modes: {passive, authenticated_passive, active_safe}`
- `run(ctx: ScanContext) -> list[RawFinding]`

**Examples of safe techniques**

- Headers: `httpx` GET/HEAD, parse `Set-Cookie`, `Content-Security-Policy`, `X-Frame-Options`, HSTS.
- CORS: analyze `Access-Control-Allow-*` vs `Origin` reflection (simulated origins from **test matrix**, not arbitrary internet).
- CSRF: **document-level** checks (SameSite, token field presence in HTML forms from **allowlisted** routes)—no cross-site trickery.
- SQLi/XSS “indicators”: **non-destructive** payloads in **test harness** or **staging** only, from fixed dictionary; classify as “suspected” with low confidence if only heuristic; **no** time-based blind mass attacks.
- IDOR/BOLA: **synthetic fixtures** (test user A/B) with known IDs from seed data—never production PII.
- SSRF: **mock** internal metadata endpoints in test env; **do not** scan internal IP ranges without explicit CIDR allowlist.
- Secrets: regex + entropy heuristics on **redacted** copies of responses (max N KB).

**OpenAPI:** Spectral or custom rules for security schemes; diff coverage vs live paths (passive discovery).

---

## 8. Logging, reporting, normalized schemas

- **Raw execution logs:** JSON lines (`structlog`), fields: `trace_id`, `scan_id`, `adapter`, `duration_ms`, `outcome`.
- **Normalized events:** `scan_event` table + JSON body (see §7 examples in deliverables below).
- **Findings:** single table + JSONB `evidence_refs`, `remediation`, `references`.

**Exports:** SARIF for GitHub/GitLab; Markdown for devs; HTML/PDF via Jinja2 + WeasyPrint (optional container).

---

## 9. Developer remediation workflow

0. **Fix application (out of band):** developers implement changes in the app/repo with **normal review** (peer review, PR, CI). mcp-cyber records outcomes via scans and status transitions—it does not apply the fix itself.
1. **Triage board:** Kanban by `status` (`open`, `in_progress`, `accepted_risk`, `fixed`, `suppressed`, `regressed`).
2. **Suppressions:** `suppression` with `reason`, `expires_at`, `approved_by`, `finding_fingerprint`.
3. **Diff:** `compare_scan_runs` highlights **new**, **resolved**, **changed_severity**.
4. **Tickets:** `export_report(format=jira_payload)` or generic JSON for Linear/GitHub Issues.
5. **Retest:** `validate_fix` triggers targeted adapter subset bound to `finding.rule_id`.

---

## 10. CI/CD integration

- **Gate job:** run `POST /scans` passive on PR preview URL; upload SARIF to GH Advanced Security; fail on `severity >= HIGH` unless baseline exception file in repo (versioned).
- **Scheduled:** nightly on staging with authenticated profile (vault creds).
- **Artifacts:** retain reports 90 days; evidence 30 days (configurable).

---

## 11. Database schema (PostgreSQL DDL)

```sql
CREATE TABLE organizations (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  org_id UUID NOT NULL REFERENCES organizations(id),
  email TEXT NOT NULL UNIQUE,
  role TEXT NOT NULL CHECK (role IN ('admin','security_engineer','manager','developer')),
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE projects (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  org_id UUID NOT NULL REFERENCES organizations(id),
  slug TEXT NOT NULL,
  name TEXT NOT NULL,
  owner_team TEXT,
  UNIQUE (org_id, slug)
);

CREATE TABLE environments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  project_id UUID NOT NULL REFERENCES projects(id),
  name TEXT NOT NULL,
  class TEXT NOT NULL CHECK (class IN ('local','dev','staging','uat','prod')),
  base_url TEXT NOT NULL,
  allowlist JSONB NOT NULL, -- hosts, path prefixes, methods
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE assets (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  environment_id UUID NOT NULL REFERENCES environments(id),
  kind TEXT NOT NULL CHECK (kind IN ('web','api','worker')),
  tags TEXT[] DEFAULT '{}',
  module TEXT,
  route_pattern TEXT
);

CREATE TABLE openapi_artifacts (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  environment_id UUID NOT NULL REFERENCES environments(id),
  version TEXT,
  storage_uri TEXT NOT NULL,
  sha256 TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE scan_profiles (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  environment_id UUID NOT NULL REFERENCES environments(id),
  name TEXT NOT NULL,
  mode TEXT NOT NULL CHECK (mode IN ('passive','authenticated_passive','active_controlled')),
  adapter_ids TEXT[] NOT NULL,
  rate_limit_rps NUMERIC NOT NULL DEFAULT 2,
  max_concurrency INT NOT NULL DEFAULT 3,
  credential_ref TEXT, -- vault path, not secret
  options JSONB NOT NULL DEFAULT '{}'
);

CREATE TABLE approvals (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id UUID NOT NULL REFERENCES scan_profiles(id),
  requester_id UUID NOT NULL REFERENCES users(id),
  approver_id UUID REFERENCES users(id),
  status TEXT NOT NULL CHECK (status IN ('pending','approved','rejected')),
  reason TEXT,
  payload_tier TEXT NOT NULL DEFAULT 'safe',
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  expires_at TIMESTAMPTZ NOT NULL
);

CREATE TABLE scan_runs (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id UUID NOT NULL REFERENCES scan_profiles(id),
  started_by UUID REFERENCES users(id),
  status TEXT NOT NULL CHECK (status IN ('queued','running','succeeded','failed','cancelled')),
  started_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  finished_at TIMESTAMPTZ,
  baseline_scan_id UUID REFERENCES scan_runs(id),
  summary JSONB,
  trace_id TEXT NOT NULL
);

CREATE TABLE scan_events (
  id BIGSERIAL PRIMARY KEY,
  scan_id UUID NOT NULL REFERENCES scan_runs(id),
  ts TIMESTAMPTZ NOT NULL DEFAULT now(),
  level TEXT NOT NULL,
  adapter TEXT NOT NULL,
  event_type TEXT NOT NULL,
  message TEXT,
  context JSONB NOT NULL DEFAULT '{}'
);

CREATE TABLE findings (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  scan_id UUID NOT NULL REFERENCES scan_runs(id),
  project_id UUID NOT NULL REFERENCES projects(id),
  environment_id UUID NOT NULL REFERENCES environments(id),
  rule_id TEXT NOT NULL,
  category TEXT NOT NULL,
  title TEXT NOT NULL,
  severity TEXT NOT NULL,
  confidence NUMERIC NOT NULL,
  cvss_score NUMERIC,
  status TEXT NOT NULL DEFAULT 'open',
  affected_asset TEXT,
  url TEXT,
  component TEXT,
  parameter TEXT,
  fingerprint TEXT NOT NULL,
  evidence JSONB NOT NULL DEFAULT '[]',
  reproduction TEXT,
  root_cause TEXT,
  remediation JSONB NOT NULL,
  references JSONB NOT NULL DEFAULT '[]',
  first_seen_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  last_seen_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  fixed_at TIMESTAMPTZ,
  owner_team TEXT,
  tags TEXT[] DEFAULT '{}',
  UNIQUE (scan_id, fingerprint)
);

CREATE TABLE suppressions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  project_id UUID NOT NULL REFERENCES projects(id),
  fingerprint TEXT NOT NULL,
  reason TEXT NOT NULL,
  created_by UUID NOT NULL REFERENCES users(id),
  expires_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE audit_log (
  id BIGSERIAL PRIMARY KEY,
  ts TIMESTAMPTZ NOT NULL DEFAULT now(),
  actor_id UUID,
  action TEXT NOT NULL,
  object_type TEXT NOT NULL,
  object_id TEXT NOT NULL,
  payload JSONB NOT NULL DEFAULT '{}'
);

CREATE INDEX idx_findings_project_status ON findings (project_id, status);
CREATE INDEX idx_findings_fingerprint ON findings (fingerprint);
CREATE INDEX idx_scan_events_scan ON scan_events (scan_id);
```

SQLAlchemy: mirror 1:1 in [`packages/cyber_db`](packages/cyber_db) with Alembic migrations.

---

## 12. Finding & event JSON examples

**Finding (normalized)**

```json
{
  "finding_id": "8b2c3d5e-...",
  "scan_id": "2f91ac1b-...",
  "project_id": "b1a0...",
  "environment": "staging",
  "category": "headers",
  "title": "Content-Security-Policy missing or weak",
  "severity": "high",
  "confidence": 0.86,
  "cvss_score": 6.5,
  "status": "open",
  "affected_asset": "https://staging.example.com",
  "url": "https://staging.example.com/login",
  "component": "global_headers",
  "parameter": null,
  "evidence_summary": "No CSP header on HTML responses (sampled 12 routes).",
  "evidence": [
    {"type": "http_response", "ref": "s3://.../evidence/abc.json", "sha256": "..."}
  ],
  "reproduction": "curl -I https://staging.example.com/login | find CSP header absent.",
  "root_cause": "Web server / middleware not configured to emit CSP.",
  "remediation": {
    "summary": "Add a strict CSP with nonces or hashes; start in report-only if needed.",
    "steps": ["Enable CSP middleware", "Inventory inline scripts", "Roll out nonce template"]
  },
  "references": [{"label": "OWASP CSP", "url": "https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html"}],
  "first_seen_at": "2026-04-06T10:00:00Z",
  "last_seen_at": "2026-04-06T10:00:00Z",
  "owner_team": "platform",
  "tags": ["quick_win"],
  "fingerprint": "sha256:..."
}
```

**scan_event**

```json
{
  "event_id": 102948,
  "scan_id": "2f91ac1b-...",
  "ts": "2026-04-06T10:00:01Z",
  "level": "info",
  "adapter": "headers_cookies",
  "event_type": "check_completed",
  "message": "Evaluated security headers for 12 URLs",
  "context": {"urls": 12, "duration_ms": 842, "trace_id": "4f6b..."}
}
```

---

## 13. Sample MCP tool contract (JSON Schema excerpt)

`run_passive_scan`:

```json
{
  "name": "run_passive_scan",
  "description": "Start a passive scan against allowlisted targets for an environment.",
  "inputSchema": {
    "type": "object",
    "required": ["profile_id"],
    "properties": {
      "profile_id": {"type": "string", "format": "uuid"},
      "target_urls": {"type": "array", "items": {"type": "string", "format": "uri"}, "maxItems": 50},
      "openapi_artifact_id": {"type": "string", "format": "uuid"},
      "note": {"type": "string", "maxLength": 500}
    }
  },
  "outputSchema": {
    "type": "object",
    "required": ["scan_id", "status"],
    "properties": {
      "scan_id": {"type": "string", "format": "uuid"},
      "status": {"type": "string", "enum": ["queued", "running"]},
      "trace_id": {"type": "string"}
    }
  }
}
```

---

## 14. Sample FastAPI endpoints

- `POST /v1/projects`, `POST /v1/environments`, `POST /v1/scan-profiles`
- `POST /v1/scans` (body: profile_id, options) → `202` + `scan_id`
- `GET /v1/scans/{id}`, `GET /v1/scans/{id}/findings`, `GET /v1/scans/{id}/events`
- `POST /v1/scans/{id}/compare/{other_id}`
- `POST /v1/findings/{id}/transition` (status + reason)
- `GET /v1/reports/{scan_id}.{fmt}`

OpenAPI 3.1 generated at `/openapi.json`.

---

## 15–20. Starter Python skeletons, Docker Compose, config, workflow, report sample, phases, risks, MVP, stack, demo, backlog, **first 14 days**

### 15. Python skeletons (abbreviated; full files when implementing)

**Orchestrator** (`cyber_engine/orchestrator.py`)

```python
from dataclasses import dataclass
from typing import Iterable
import structlog
from cyber_engine.adapters.base import Adapter, ScanContext, RawFinding
from cyber_engine.normalizer import Normalizer
from cyber_engine.policy_engine import PolicyEngine

log = structlog.get_logger()

@dataclass
class ScanContext:
    scan_id: str
    profile: dict
    environment: dict
    trace_id: str
    http_client: object  # httpx.AsyncClient

class Orchestrator:
    def __init__(self, adapters: dict[str, Adapter], policy: PolicyEngine, norm: Normalizer):
        self.adapters = adapters
        self.policy = policy
        self.norm = norm

    async def run(self, ctx: ScanContext) -> list[dict]:
        self.policy.assert_permitted(ctx)
        findings: list[dict] = []
        for adapter_id in ctx.profile["adapter_ids"]:
            adapter = self.adapters[adapter_id]
            log.info("adapter_start", adapter=adapter_id, scan_id=ctx.scan_id)
            raw: Iterable[RawFinding] = await adapter.run(ctx)
            findings.extend(self.norm.normalize_batch(raw, ctx))
        return findings
```

**Headers/cookies adapter** (excerpt)

```python
REQUIRED = ["x-content-type-options", "x-frame-options"]
async def run(ctx: ScanContext):
    url = ctx.environment["base_url"]
    resp = await ctx.http_client.get(url, follow_redirects=True)
    headers = {k.lower(): v for k, v in resp.headers.items()}
    findings = []
    if "content-security-policy" not in headers:
        findings.append(RawFinding(rule_id="hdr.csp.missing", severity="high", ...))
    # Set-Cookie flags check on session cookies...
    return findings
```

**Normalizer**, **report generator**, **policy engine** — implement fingerprinting, SARIF mapping, and `assert_permitted` (environment class × mode × approval).

### 16. Example `docker-compose.yml`

```yaml
services:
  postgres:
    image: postgres:16
    environment:
      POSTGRES_USER: cyber
      POSTGRES_PASSWORD: cyber
      POSTGRES_DB: cyber
    ports: ["5432:5432"]
    volumes: ["pgdata:/var/lib/postgresql/data"]
  api:
    build: ./packages/cyber_api
    environment:
      DATABASE_URL: postgresql+asyncpg://cyber:cyber@postgres:5432/cyber
      CYBER_REQUIRE_ALLOWLIST: "true"
      CYBER_LOG_LEVEL: INFO
    depends_on: [postgres]
    ports: ["8000:8000"]
  worker:
    build: ./packages/cyber_worker
    environment:
      DATABASE_URL: postgresql://cyber:cyber@postgres:5432/cyber
    depends_on: [postgres]
  # redis optional:
  # redis:
  #   image: redis:7
volumes:
  pgdata: {}
```

### 17. Example config `configs/default.policy.yaml`

```yaml
global:
  require_allowlist: true
  kill_switch: false
environment_rules:
  prod:
    allowed_modes: [passive]
    max_rps: 1
  staging:
    allowed_modes: [passive, authenticated_passive, active_controlled]
    active_requires_approval: true
active_scan:
  payload_tier: safe
  forbid_payloads_matching: ["'; drop", "sleep(", "/etc/passwd"]
```

### 18. End-to-end scan workflow (one paragraph)

Register **org** → **project** → **environment** (`staging`, base URL, allowlist) → upload **OpenAPI** → create **passive profile** → `POST /v1/scans` → worker runs adapters → events logged → findings normalized → UI/MCP lists findings → developer marks **in_progress** → fix → `validate_fix` re-scan → `compare_scan_runs` shows resolved → optional **SARIF** upload to CI.

### 19. Example report excerpt (Markdown)

```markdown
# Security posture — project billing-api / staging — run 2f91ac1b

## Executive summary
- Open findings: 14 (High: 3, Medium: 6, Low: 5)
- Delta vs baseline: +2 new, -4 resolved
- Top risk: Missing CSP on authenticated routes

## Developer checklist (top 10)
1. [High] CSP missing — /login, /app/dashboard — see finding F-12
2. [High] Cookie without Secure — F-03
...

## Quick wins
- Enable HSTS (1 config change) — findings F-01, F-07
```

### 20. Phased rollout

| Phase | Scope |
|-------|--------|
| **1 MVP** | Postgres + FastAPI + passive adapters + normalizer + MD/JSON reports + MCP read-mostly tools + Docker Compose |
| **2 Team** | UI, authenticated Playwright flows, OpenAPI diff, SARIF, CI gate, suppressions |
| **3 Enterprise** | SSO, RBAC hardening, vault integration, object storage, HA workers + Redis queue, approvals |
| **4 Analytics** | Trends, SLAs, fleet dashboards, custom business rules, fuzzing harness integration (still allowlisted) |

### Risks & tradeoffs

- **False positives** in heuristic XSS/SQLi → mitigate with confidence + tiered staging-only rules.
- **Credential handling** → highest operational risk; invest early in vault + rotation.
- **Playwright** in CI → heavy; use separate worker pool.

### Minimal MVP definition

Org/project/env registry + allowlist + passive header/cookie/CORS/CSP/TLS basic + OpenAPI ingest + scan run + findings API + Markdown/JSON export + `compare_scan_runs` + MCP tools: `register_environment`, `run_passive_scan`, `list_findings`, `get_finding_detail`, `export_report`, `summarize_security_posture`.

### Recommended tech stack

Python 3.12, FastAPI, Pydantic v2, SQLAlchemy 2 + Alembic, httpx, structlog, Celery or RQ + Redis (Phase 2), Playwright (Python), Spectral (CLI or Node sidecar), PostgreSQL 16, MinIO for dev evidence, Keycloak/OIDC for enterprise.

### Sample demo scenario (staging Laravel app)

1. Register `https://staging.upos.local` with path prefix `/` and host allowlist.
2. Import OpenAPI from `https://staging.upos.local/api/openapi.json` (if exposed) or file upload.
3. Run passive scan + **RBAC matrix** check from exported `routes.json` artifact generated in CI.
4. Show 20–40 findings; fix CSP in nginx; re-run; compare shows CSP resolved.

### Prioritized issue backlog (sample tickets)

1. Bootstrap monorepo + Docker Compose + Alembic initial schema
2. Policy engine + allowlist enforcement tests
3. `headers_cookies` + `tls_basic` adapters
4. Normalizer + fingerprint + DB persistence
5. FastAPI scans CRUD + OpenAPI export
6. MCP server tool wrappers + auth
7. SARIF exporter v1
8. Playwright authenticated adapter behind feature flag
9. Suppressions + audit log UI
10. GitHub Action template for SARIF upload

---

## Build order for the first 14 days

| Day | Deliverable |
|-----|-------------|
| 1 | Repo skeleton, `cyber_core` models (Finding, RawFinding), structlog setup |
| 2 | Postgres + SQLAlchemy models + Alembic migration matching DDL |
| 3 | FastAPI health + project/env CRUD + JWT dev auth |
| 4 | Policy engine + unit tests (prod blocks active) |
| 5 | `httpx` client factory + rate limiter |
| 6 | Orchestrator skeleton + `headers_cookies` adapter |
| 7 | Normalizer + fingerprint + persist findings |
| 8 | Scan run API + background execution (async task) |
| 9 | Events logging + `compare_scan_runs` logic |
| 10 | Markdown + JSON report generator |
| 11 | MCP server (`run_passive_scan`, `list_findings`, `get_finding_detail`) |
| 12 | OpenAPI artifact upload + spectral wrapper |
| 13 | Docker Compose e2e smoke + integration tests |
| 14 | Demo staging scan + documentation + security tests for platform |

---

## Dashboard/UI (suggested)

**Next.js + Tremor or MUI:** pages: Posture overview, Findings (filters), Run history, Regression timeline, Evidence viewer (signed URLs), Remediation board, Policy gates, Audit explorer. Roles: developer (project-scoped), security (cross-project read + approvals), manager (summaries only).

---

## Enterprise deployment

- **Local:** Docker Compose (above).
- **K8s:** Deploy `api`, `worker`, `postgres` (managed), `redis`, `minio`/S3; Ingress + OIDC; NetworkPolicy isolating workers from DB; secrets via External Secrets Operator.

---

## Scaling & multitenancy

- **Tenant = org_id**: all queries scoped; optional separate DB schema per org later.
- **Project isolation:** row-level security policy in Postgres or application-level mandatory filter.
- **Secrets:** Vault dynamic creds for workers; no env vars for long-lived passwords in production.

---

## Policy gates (example YAML rules)

```yaml
gates:
  ci_default:
    block_if:
      - severity: high
        status: open
        categories: [authz, injection]
    allow_suppressions: true
```

---

### Implementation note (plan mode)

This document is the blueprint. After you approve, the implementation step creates the monorepo (sibling directory or `mcp/mcp-cyber/`), wires packages, and lands MVP code paths above—**no destructive scanners**, strict allowlists, and tests under `tests/security/` for policy bypass attempts.
