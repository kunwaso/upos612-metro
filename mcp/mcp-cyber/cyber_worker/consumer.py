"""Run as a separate process when using Redis-backed scan dispatch (see CYBER_REDIS_URL)."""

from __future__ import annotations

import asyncio
import os
import sys
import uuid

import structlog

log = structlog.get_logger()


async def _loop() -> None:
    url = (os.environ.get("CYBER_REDIS_URL") or os.environ.get("REDIS_URL") or "").strip()
    if not url:
        log.error("redis_url_missing", hint="Set CYBER_REDIS_URL")
        sys.exit(1)
    try:
        import redis.asyncio as redis  # type: ignore[import-untyped]
    except ImportError as e:
        log.error("redis_import_failed", error=str(e))
        sys.exit(1)

    from cyber_worker.tasks import execute_scan

    client = redis.from_url(url, decode_responses=True)
    log.info("scan_worker_started", queue="cyber:scans:queue")
    try:
        while True:
            item = await client.brpop("cyber:scans:queue", timeout=5)
            if not item:
                continue
            _, sid = item
            try:
                await execute_scan(uuid.UUID(str(sid)))
            except Exception:
                log.exception("scan_job_failed", scan_id=sid)
    finally:
        await client.aclose()


def main() -> None:
    asyncio.run(_loop())


if __name__ == "__main__":
    main()
