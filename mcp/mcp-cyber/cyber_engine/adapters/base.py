from __future__ import annotations

from abc import ABC, abstractmethod
from dataclasses import dataclass, field
from typing import TYPE_CHECKING, Any

import httpx

from cyber_core.models.finding import RawFinding

if TYPE_CHECKING:
    from cyber_engine.rate_limit import AsyncRateLimiter


@dataclass
class ScanContext:
    scan_id: str
    trace_id: str
    profile_id: str
    mode: str
    adapter_ids: list[str]
    rate_limit_rps: float
    max_concurrency: int
    environment_id: str
    environment_name: str
    environment_class: str
    base_url: str
    allowlist: dict[str, Any]
    project_id: str
    options: dict[str, Any] = field(default_factory=dict)
    target_urls: list[str] = field(default_factory=list)
    http_client: httpx.AsyncClient | None = None
    rate_limiter: AsyncRateLimiter | None = None


class Adapter(ABC):
    id: str = "base"
    version: str = "1"

    @abstractmethod
    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        raise NotImplementedError
