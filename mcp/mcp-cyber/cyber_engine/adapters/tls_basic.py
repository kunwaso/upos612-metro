"""TLS certificate and handshake checks for HTTPS targets (passive, allowlisted)."""

from __future__ import annotations

import asyncio
import socket
import ssl
from datetime import datetime, timedelta, timezone
from urllib.parse import urlparse

import structlog
from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext

log = structlog.get_logger()

_LOCAL_HOSTS = frozenset(
    {
        "localhost",
        "127.0.0.1",
        "::1",
        "[::1]",
    }
)

_WARN_DAYS = 30


def _tls_targets(ctx: ScanContext) -> list[tuple[str, str, int]]:
    """(sample_url, host, port) unique per host:port, HTTPS only, skip local hosts."""
    urls = list(ctx.target_urls) if ctx.target_urls else [ctx.base_url]
    seen: set[tuple[str, int]] = set()
    out: list[tuple[str, str, int]] = []
    for raw in urls:
        p = urlparse(raw)
        if (p.scheme or "").lower() != "https":
            continue
        host = (p.hostname or "").strip().lower()
        if not host or host in _LOCAL_HOSTS:
            continue
        port = int(p.port or 443)
        key = (host, port)
        if key in seen:
            continue
        seen.add(key)
        out.append((raw, host, port))
    return out


def _check_one(sample_url: str, host: str, netloc_port: int) -> list[RawFinding]:
    findings: list[RawFinding] = []
    ctx = ssl.create_default_context()
    cert = None
    try:
        with socket.create_connection((host, netloc_port), timeout=12) as sock:
            with ctx.wrap_socket(sock, server_hostname=host) as ssock:
                cert = ssock.getpeercert()
    except ssl.SSLError as e:
        findings.append(
            RawFinding(
                rule_id="tls.handshake_or_verify_failed",
                category="transport",
                title="TLS handshake or certificate verification failed",
                severity="high",
                confidence=0.85,
                url=sample_url,
                affected_asset=f"{host}:{netloc_port}",
                reproduction=f"Open TLS to {host}:{netloc_port}: {e!s}",
                root_cause="Chain mismatch, hostname mismatch, expired cert, or unsupported protocol.",
                remediation=RemediationBlock(
                    summary="Fix certificate chain and server TLS configuration.",
                    steps=["Use a public CA or trusted internal CA", "Ensure SNI and hostname match"],
                ),
                tags=["tls"],
            )
        )
        return findings
    except OSError as e:
        findings.append(
            RawFinding(
                rule_id="tls.connection_failed",
                category="transport",
                title="Could not complete TLS connection",
                severity="medium",
                confidence=0.7,
                url=sample_url,
                affected_asset=f"{host}:{netloc_port}",
                reproduction=str(e),
                remediation=RemediationBlock(summary="Check firewall, port, and service availability."),
                tags=["tls"],
            )
        )
        return findings

    if not cert:
        findings.append(
            RawFinding(
                rule_id="tls.no_peer_cert",
                category="transport",
                title="No peer certificate details returned after handshake",
                severity="medium",
                confidence=0.5,
                url=sample_url,
                affected_asset=f"{host}:{netloc_port}",
                remediation=RemediationBlock(summary="Inspect server TLS stack and client trust store."),
                tags=["tls"],
            )
        )
        return findings

    nafter = cert.get("notAfter")
    if nafter:
        try:
            expiry = datetime.strptime(nafter, r"%b %d %H:%M:%S %Y %Z").replace(tzinfo=timezone.utc)
        except ValueError:
            expiry = None
        if expiry:
            now = datetime.now(timezone.utc)
            if expiry <= now:
                findings.append(
                    RawFinding(
                        rule_id="tls.cert.expired",
                        category="transport",
                        title="TLS server certificate is expired",
                        severity="high",
                        confidence=0.95,
                        url=sample_url,
                        affected_asset=f"{host}:{netloc_port}",
                        reproduction=f"notAfter={nafter}",
                        remediation=RemediationBlock(summary="Renew and deploy a valid certificate."),
                        tags=["tls", "quick_win"],
                    )
                )
            elif expiry - now <= timedelta(days=_WARN_DAYS):
                findings.append(
                    RawFinding(
                        rule_id="tls.cert.expiring_soon",
                        category="transport",
                        title=f"TLS certificate expires within {_WARN_DAYS} days",
                        severity="medium",
                        confidence=0.9,
                        url=sample_url,
                        affected_asset=f"{host}:{netloc_port}",
                        reproduction=f"notAfter={nafter}",
                        remediation=RemediationBlock(
                            summary="Schedule certificate renewal before expiry.",
                            steps=[],
                        ),
                        tags=["tls"],
                    )
                )

    return findings


class TlsBasicAdapter(Adapter):
    id = "tls_basic"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        targets = _tls_targets(ctx)
        if not targets:
            return []
        out: list[RawFinding] = []
        for sample_url, host, port in targets:
            log.info("tls_basic_check", host=host, port=port, scan_id=ctx.scan_id)
            part = await asyncio.to_thread(_check_one, sample_url, host, port)
            out.extend(part)
        return out
