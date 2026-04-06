from collections.abc import AsyncGenerator
from typing import Annotated

from fastapi import Depends
from sqlalchemy.ext.asyncio import AsyncSession

from cyber_db.session import get_async_session

DbSession = Annotated[AsyncSession, Depends(get_async_session)]
