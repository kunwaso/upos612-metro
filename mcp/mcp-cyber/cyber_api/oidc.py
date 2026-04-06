"""
OIDC / SSO integration (Phase 3 enterprise).

Replace dev JWT with bearer validation against your IdP (Keycloak, Azure AD, Okta).

Planned:
- JWKS URI + issuer + audience validation
- Map IdP groups to mcp-cyber roles (developer, security_engineer, manager, admin)
- Optional mTLS for service accounts (CI, MCP)

MVP: not wired; use cyber_api.security.create_dev_token / seed_demo for local use.
"""

from __future__ import annotations

# Placeholder for future: OIDCBearerValidator
