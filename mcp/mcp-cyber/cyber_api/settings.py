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

    dev_secret: str = "change-me-dev-only"
    policy_path: str | None = None
    artifacts_dir: str = "./data/artifacts"
    require_allowlist: bool | None = None

    # uvicorn bind (use CYBER_LISTEN_PORT=8686 + CYBER_API_URL=http://127.0.0.1:8686 for MCP)
    listen_host: str = "127.0.0.1"
    listen_port: int = 8000
    # CYBER_RELOAD=true enables uvicorn --reload (dev only)
    reload: bool = False

    # Unauthenticated / + /dashboard/api/* (read-only). Disable when API is reachable beyond localhost.
    dashboard_enabled: bool = True


settings = Settings()
