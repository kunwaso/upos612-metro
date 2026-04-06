import os
from logging.config import fileConfig

from alembic import context
from sqlalchemy import engine_from_config, pool

from cyber_db.models import Base

config = context.config
if config.config_file_name is not None:
    fileConfig(config.config_file_name)

target_metadata = Base.metadata


def get_sync_url() -> str:
    """Alembic uses a sync driver; prefer psycopg v3 (postgresql+psycopg), not psycopg2."""
    url = os.environ.get("DATABASE_URL_SYNC")
    if not url:
        async_url = os.environ.get(
            "DATABASE_URL", "postgresql+asyncpg://cyber:cyber@127.0.0.1:5432/cyber"
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
