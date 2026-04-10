import secrets
from pathlib import Path

from pydantic import AliasChoices, Field
from pydantic_settings import BaseSettings, SettingsConfigDict

# Resolve `.env` next to repo root (mcp/mcp-cyber), not only cwd
_MCP_CYBER_ROOT = Path(__file__).resolve().parent.parent
_ENV_FILE = _MCP_CYBER_ROOT / ".env"


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_prefix="CYBER_",
        extra="ignore",
        env_file=str(_ENV_FILE) if _ENV_FILE.is_file() else None,
        env_file_encoding="utf-8",
    )

    # Accept CYBER_DATABASE_URL or plain DATABASE_URL (common with other stacks)
    database_url: str = Field(
        default="postgresql+asyncpg://cyber:cyber@127.0.0.1:5432/cyber",
        validation_alias=AliasChoices("CYBER_DATABASE_URL", "DATABASE_URL"),
    )

    # Per-process fallback for local dev only; set CYBER_DEV_SECRET explicitly for stable multi-process behavior.
    dev_secret: str = Field(default_factory=lambda: secrets.token_urlsafe(48))
    policy_path: str | None = None
    artifacts_dir: str = "./data/artifacts"
    require_allowlist: bool | None = None

    # uvicorn bind (use CYBER_LISTEN_PORT=8686 + CYBER_API_URL=http://127.0.0.1:8686 for MCP)
    listen_host: str = "127.0.0.1"
    listen_port: int = 8000
    # CYBER_RELOAD=true enables uvicorn --reload (dev only)
    reload: bool = False

    # Dashboard is opt-in; enable explicitly for local troubleshooting.
    dashboard_enabled: bool = False

    # --- OIDC (Phase 2): when issuer + audience + JWKS URL are all set, RS256/ES256 tokens are accepted first.
    oidc_issuer: str = ""
    oidc_audience: str = ""
    oidc_jwks_url: str = ""
    # Allow local HS256 dev JWT when OIDC is not configured.
    auth_allow_dev_jwt: bool = True
    # If true, HS256 dev JWT is still accepted when OIDC verification fails.
    auth_dev_jwt_alongside_oidc: bool = False
    oidc_default_org_id: str = ""
    oidc_email_claim: str = "email"
    oidc_org_id_claim: str = "org_id"
    oidc_groups_claim: str = "groups"
    # Comma-separated group names (case-insensitive exact match) → admin
    oidc_admin_groups: str = "admin,cyber-admin,mcp-cyber-admin"
    oidc_security_groups: str = "security,security_engineer,sec-eng"
    oidc_manager_groups: str = "manager,product-manager"

    # Phase 3: optional Redis LPUSH for scan jobs (API enqueues; run cyber_worker.consumer separately).
    redis_url: str = Field(default="", validation_alias=AliasChoices("CYBER_REDIS_URL", "REDIS_URL"))

    # Phase 4: analytics / SLA defaults
    sla_high_critical_days: int = 14
    analytics_max_trend_days: int = 90

    # Phase 5: optional YAML tags on raw findings before normalize
    business_rules_path: str | None = None


settings = Settings()
