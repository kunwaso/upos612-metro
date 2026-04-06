import os
from collections.abc import AsyncGenerator

from sqlalchemy.ext.asyncio import AsyncSession, async_sessionmaker, create_async_engine
from sqlalchemy.orm import DeclarativeBase


def get_database_url() -> str:
    url = os.environ.get("DATABASE_URL", "postgresql+asyncpg://cyber:cyber@127.0.0.1:5432/cyber")
    return url


class Base(DeclarativeBase):
    pass


engine = create_async_engine(get_database_url(), echo=False)
async_session_factory = async_sessionmaker(engine, class_=AsyncSession, expire_on_commit=False)


async def get_async_session() -> AsyncGenerator[AsyncSession, None]:
    async with async_session_factory() as session:
        yield session
