"""HTTP client for MCP tools → FastAPI."""

from __future__ import annotations

import os
from typing import Any

import httpx


class CyberApiClient:
    def __init__(self, base_url: str | None = None, token: str | None = None) -> None:
        self.base_url = (base_url or os.environ.get("CYBER_API_URL", "http://127.0.0.1:8000")).rstrip("/")
        self.token = token or os.environ.get("CYBER_API_TOKEN", "")

    def _headers(self) -> dict[str, str]:
        h = {"Content-Type": "application/json"}
        if self.token:
            h["Authorization"] = f"Bearer {self.token}"
        return h

    async def post(self, path: str, json: dict | None = None) -> dict[str, Any]:
        async with httpx.AsyncClient(timeout=120.0) as client:
            r = await client.post(f"{self.base_url}{path}", json=json or {}, headers=self._headers())
            r.raise_for_status()
            return r.json()

    async def get(self, path: str) -> dict[str, Any] | list:
        async with httpx.AsyncClient(timeout=120.0) as client:
            r = await client.get(f"{self.base_url}{path}", headers=self._headers())
            r.raise_for_status()
            return r.json()
