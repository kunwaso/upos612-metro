"""
Run the API with host/port from settings (see CYBER_LISTEN_HOST, CYBER_LISTEN_PORT, CYBER_RELOAD).

Usage (from mcp/mcp-cyber directory):

    python -m cyber_api

Or:

    mcp-cyber-api
"""

from __future__ import annotations

import uvicorn

from cyber_api.settings import settings


def main() -> None:
    uvicorn.run(
        "cyber_api.main:app",
        host=settings.listen_host,
        port=settings.listen_port,
        reload=settings.reload,
    )


if __name__ == "__main__":
    main()
