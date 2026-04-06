"""Passive security headers, cookies, CORS, CSP, clickjacking, HSTS."""

from __future__ import annotations

from typing import Any
from urllib.parse import urlparse

import httpx
import structlog
from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext

log = structlog.get_logger()


def _urls_to_check(ctx: ScanContext) -> list[str]:
    if ctx.target_urls:
        return ctx.target_urls[:50]
    return [ctx.base_url.rstrip("/") + "/"]


class HeadersCookiesAdapter(Adapter):
    id = "headers_cookies"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        client = ctx.http_client
        if not client:
            return []
        findings: list[RawFinding] = []
        for url in _urls_to_check(ctx):
            if ctx.rate_limiter:
                await ctx.rate_limiter.acquire()
            try:
                resp = await client.get(url, follow_redirects=True, timeout=30.0)
            except Exception as e:
                log.warning("header_fetch_failed", url=url, error=str(e))
                continue
            h = {k.lower(): v for k, v in resp.headers.items()}
            findings.extend(_check_headers(url, h))
            findings.extend(_check_cookies(url, resp.headers))
            findings.extend(_tls_notes(ctx.base_url, url))
        return findings


def _check_headers(url: str, h: dict[str, str]) -> list[RawFinding]:
    out: list[RawFinding] = []
    if "content-security-policy" not in h and "content-security-policy-report-only" not in h:
        out.append(
            RawFinding(
                rule_id="hdr.csp.missing",
                category="headers",
                title="Content-Security-Policy not present",
                severity="high",
                confidence=0.9,
                cvss_score=6.5,
                url=url,
                component="http_response_headers",
                reproduction=f"HTTP GET {url} and observe response headers.",
                root_cause="Server does not send CSP.",
                remediation=RemediationBlock(
                    summary="Add a Content-Security-Policy appropriate for your app.",
                    steps=[
                        "Start with report-only mode if needed",
                        "Use nonces or hashes for inline script",
                    ],
                ),
                references=[
                    {
                        "label": "OWASP CSP Cheat Sheet",
                        "url": "https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html",
                    }
                ],
                tags=["quick_win"],
            )
        )
    if h.get("x-content-type-options", "").lower() != "nosniff":
        out.append(
            RawFinding(
                rule_id="hdr.xcto.missing",
                category="headers",
                title="X-Content-Type-Options: nosniff missing",
                severity="medium",
                confidence=0.85,
                url=url,
                remediation=RemediationBlock(
                    summary="Set X-Content-Type-Options: nosniff on HTML responses.",
                    steps=["Configure reverse proxy or framework middleware"],
                ),
            )
        )
    xfo = h.get("x-frame-options", "").upper()
    csp = h.get("content-security-policy", "") + h.get("content-security-policy-report-only", "")
    if not xfo and "frame-ancestors" not in csp.lower():
        out.append(
            RawFinding(
                rule_id="hdr.clickjacking",
                category="headers",
                title="Clickjacking protection missing (X-Frame-Options or CSP frame-ancestors)",
                severity="medium",
                confidence=0.8,
                url=url,
                remediation=RemediationBlock(
                    summary="Set X-Frame-Options: DENY/SAMEORIGIN or CSP frame-ancestors.",
                    steps=[],
                ),
            )
        )
    if "strict-transport-security" not in h and urlparse(url).scheme == "https":
        out.append(
            RawFinding(
                rule_id="hdr.hsts.missing",
                category="headers",
                title="Strict-Transport-Security (HSTS) not set on HTTPS response",
                severity="medium",
                confidence=0.75,
                url=url,
                tags=["quick_win"],
                remediation=RemediationBlock(
                    summary="Enable HSTS with appropriate max-age and includeSubDomains as needed.",
                    steps=[],
                ),
            )
        )
    acao = h.get("access-control-allow-origin", "")
    acac = h.get("access-control-allow-credentials", "").lower()
    if acao == "*" and acac == "true":
        out.append(
            RawFinding(
                rule_id="cors.wildcard_credentials",
                category="cors",
                title="CORS allows wildcard origin with credentials",
                severity="high",
                confidence=0.7,
                url=url,
                root_cause="Invalid combination per CORS specification; browsers may block, but misconfiguration is risky.",
                remediation=RemediationBlock(
                    summary="Use explicit origins when credentials are allowed.",
                    steps=[],
                ),
            )
        )
    return out


def _set_cookie_name(line: str) -> str:
    first = line.split(";", 1)[0]
    if "=" in first:
        return first.split("=", 1)[0].strip()
    return first.strip() or "cookie"


def _check_cookies(url: str, headers: httpx.Headers) -> list[RawFinding]:
    out: list[RawFinding] = []
    raw = [v for k, v in headers.multi_items() if k.lower() == "set-cookie"]
    for line in raw:
        lower = line.lower()
        cname = _set_cookie_name(line)
        if "session" in lower or "sess" in lower or "auth" in lower:
            if "secure" not in lower and urlparse(url).scheme == "https":
                out.append(
                    RawFinding(
                        rule_id="cookie.secure.missing",
                        category="session",
                        title="Session cookie without Secure flag",
                        severity="high",
                        confidence=0.65,
                        url=url,
                        parameter=cname,
                        remediation=RemediationBlock(
                            summary="Set Secure (and HttpOnly, SameSite) on session cookies.",
                            steps=[],
                        ),
                    )
                )
            if "httponly" not in lower:
                out.append(
                    RawFinding(
                        rule_id="cookie.httponly.missing",
                        category="session",
                        title="Cookie may be accessible to JavaScript (HttpOnly not set)",
                        severity="medium",
                        confidence=0.6,
                        url=url,
                        parameter=cname,
                        remediation=RemediationBlock(
                            summary="Set HttpOnly on sensitive cookies.",
                            steps=[],
                        ),
                    )
                )
            if "samesite" not in lower:
                out.append(
                    RawFinding(
                        rule_id="cookie.samesite.missing",
                        category="session",
                        title="SameSite not set on cookie",
                        severity="low",
                        confidence=0.55,
                        url=url,
                        parameter=cname,
                        remediation=RemediationBlock(
                            summary="Set SameSite=Lax or Strict where appropriate.",
                            steps=[],
                        ),
                    )
                )
    return out


def _tls_notes(base_url: str, sample_url: str) -> list[RawFinding]:
    """Non-intrusive: note if base URL is http (no TLS)."""
    out: list[RawFinding] = []
    if urlparse(base_url).scheme == "http":
        out.append(
            RawFinding(
                rule_id="tls.http_base",
                category="transport",
                title="Base URL uses HTTP, not HTTPS",
                severity="high",
                confidence=0.95,
                affected_asset=base_url,
                url=sample_url,
                remediation=RemediationBlock(
                    summary="Serve the application over HTTPS in non-local environments.",
                    steps=["Terminate TLS at load balancer or reverse proxy"],
                ),
            )
        )
    return out
