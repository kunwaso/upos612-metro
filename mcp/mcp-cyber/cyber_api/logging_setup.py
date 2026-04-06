"""Request-scoped logging (trace id from header)."""

from uuid import uuid4

import structlog
from fastapi import FastAPI, Request


def setup_app_logging(app: FastAPI) -> None:
    @app.middleware("http")
    async def add_trace(request: Request, call_next):
        tid = request.headers.get("x-trace-id") or str(uuid4())
        structlog.contextvars.clear_contextvars()
        structlog.contextvars.bind_contextvars(trace_id=tid)
        response = await call_next(request)
        response.headers["x-trace-id"] = tid
        return response
