"""Simple async rate limiter (token bucket per scan)."""

from __future__ import annotations

import asyncio
import time


class AsyncRateLimiter:
    def __init__(self, rps: float) -> None:
        self._interval = 1.0 / max(rps, 0.1)
        self._lock = asyncio.Lock()
        self._next_at = 0.0

    async def acquire(self) -> None:
        async with self._lock:
            now = time.monotonic()
            if now < self._next_at:
                await asyncio.sleep(self._next_at - now)
            self._next_at = time.monotonic() + self._interval
