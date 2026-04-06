"""Optional Redis queue for scan runs (Phase 3 HA workers)."""

from __future__ import annotations

import uuid


SCAN_QUEUE_KEY = "cyber:scans:queue"


async def enqueue_scan(redis_url: str, scan_id: uuid.UUID) -> bool:
    """
    LPUSH scan_id onto the queue. Returns False if redis is unavailable or not installed.
    """
    url = (redis_url or "").strip()
    if not url:
        return False
    try:
        import redis.asyncio as redis  # type: ignore[import-untyped]
    except ImportError:
        return False
    client = redis.from_url(url, decode_responses=True)
    try:
        await client.lpush(SCAN_QUEUE_KEY, str(scan_id))
        return True
    except Exception:
        return False
    finally:
        await client.aclose()
