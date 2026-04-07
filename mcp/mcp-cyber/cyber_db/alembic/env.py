import os
from logging.config import fileConfig
from pathlib import Path

from alembic import context
from sqlalchemy import engine_from_config, pool

from cyber_db.models import Base

# Auto-load .env sitting next to the project root (mcp/mcp-cyber/.env) so
# `python -m alembic upgrade head` works without exporting env vars manually.
_root = Path(__file__).resolve().parent.parent.parent  # mcp/mcp-cyber/
_env_file = _root / ".env"
if _env_file.is_file():
    for _line in _env_file.read_text(encoding="utf-8").splitlines():
        _line = _line.strip()
        if not _line or _line.startswith("#") or "=" not in _line:
            continue
        _k, _, _v = _line.partition("=")
        _k = _k.strip(); _v = _v.strip()
        if _k and _v:
            os.environ.setdefault(_k, _v)

config = context.config
if config.config_file_name is not None:
    fileConfig(config.config_file_name)

target_metadata = Base.metadata


def get_sync_url() -> str:
    """Alembic uses a sync driver; prefer psycopg v3 (postgresql+psycopg), not psycopg2.
    Accepts CYBER_DATABASE_URL_SYNC, DATABASE_URL_SYNC, CYBER_DATABASE_URL, or DATABASE_URL."""
    url = (
        os.environ.get("CYBER_DATABASE_URL_SYNC")
        or os.environ.get("DATABASE_URL_SYNC")
    )
    if not url:
        async_url = (
            os.environ.get("CYBER_DATABASE_URL")
            or os.environ.get("DATABASE_URL")
            or "postgresql+asyncpg://cyber:cyber@127.0.0.1:5432/cyber"
        )
        url = async_url.replace("+asyncpg", "+psycopg")
    # Accept postgres:// / postgresql:// without a dialect → force psycopg3 (project dependency)
    if url.startswith("postgres://"):
        url = "postgresql+psycopg://" + url[len("postgres://") :]
    elif url.startswith("postgresql://") and "+psycopg" not in url and "+psycopg2" not in url:
        url = "postgresql+psycopg://" + url[len("postgresql://") :]
    return url


def run_migrations_offline() -> None:
    url = get_sync_url()
    context.configure(
        url=url,
        target_metadata=target_metadata,
        literal_binds=True,
        dialect_opts={"paramstyle": "named"},
    )
    with context.begin_transaction():
        context.run_migrations()


def run_migrations_online() -> None:
    configuration = config.get_section(config.config_ini_section) or {}
    configuration["sqlalchemy.url"] = get_sync_url()
    connectable = engine_from_config(
        configuration,
        prefix="sqlalchemy.",
        poolclass=pool.NullPool,
    )
    with connectable.connect() as connection:
        context.configure(connection=connection, target_metadata=target_metadata)
        with context.begin_transaction():
            context.run_migrations()


if context.is_offline_mode():
    run_migrations_offline()
else:
    run_migrations_online()
