"""
Authenticated flows: browser login (Playwright) or Bearer token, then passive header checks.

Requires profile mode ``authenticated_passive``, allowlisted targets (including login URL),
and ``CYBER_PLAYWRIGHT_ADAPTER=1`` for browser flows. Credentials come from vault resolution
(``resolved_credentials`` in scan context); never log secrets.
"""

from __future__ import annotations

import os
from dataclasses import replace

import httpx
import structlog
from cyber_core.models.finding import RawFinding, RemediationBlock

from cyber_engine.adapters.base import Adapter, ScanContext
from cyber_engine.adapters.headers_cookies import HeadersCookiesAdapter
from cyber_engine.evidence import object_store

log = structlog.get_logger()

_BASE_UA = "mcp-cyber/0.1 (defensive scan; authorized; authenticated-passive)"


def _adapter_env_enabled() -> bool:
    return os.environ.get("CYBER_PLAYWRIGHT_ADAPTER", "").lower() in ("1", "true", "yes")


def _config_finding(rule_id: str, title: str, severity: str = "high") -> RawFinding:
    return RawFinding(
        rule_id=rule_id,
        category="config",
        title=title,
        severity=severity,
        confidence=1.0,
        remediation=RemediationBlock(
            summary="Fix scan profile options, vault reference, and allowlist.",
            steps=[
                "Use mode authenticated_passive for this adapter",
                "Set credential_ref and CYBER_VAULT_JSON / CYBER_VAULT_FILE / Vault KV",
                "Set playwright_login_url and selectors when using password login",
            ],
        ),
    )


class PlaywrightSessionAdapter(Adapter):
    id = "playwright_session"

    async def run(self, ctx: ScanContext) -> list[RawFinding]:
        if ctx.mode != "authenticated_passive":
            log.info("playwright_skipped_mode", mode=ctx.mode, scan_id=ctx.scan_id)
            return []

        opts = ctx.options
        creds: dict[str, str] = dict(opts.get("resolved_credentials") or {})
        username, password, token = creds.get("username"), creds.get("password"), creds.get("token")

        # Bearer-only: no Playwright; still gated by mode + allowlist.
        if token and not (username and password):
            headers = {"User-Agent": _BASE_UA, "Authorization": f"Bearer {token}"}
            async with httpx.AsyncClient(headers=headers, follow_redirects=True) as authed:
                return await HeadersCookiesAdapter().run(replace(ctx, http_client=authed))

        if not _adapter_env_enabled():
            log.info("playwright_adapter_disabled", scan_id=ctx.scan_id)
            return []

        if not username or not password:
            log.warning("playwright_missing_password_credentials", scan_id=ctx.scan_id)
            return [
                _config_finding(
                    "auth.scan.no_credentials",
                    "Authenticated scan missing username/password (or token) from vault",
                )
            ]

        login_url = str(opts.get("playwright_login_url") or "").strip()
        if not login_url:
            return [
                _config_finding(
                    "auth.scan.no_login_url",
                    "playwright_login_url is required for password-based authenticated scans",
                )
            ]

        try:
            from playwright.async_api import async_playwright
        except ImportError:
            log.warning("playwright_not_installed")
            return [
                _config_finding(
                    "auth.playwright.missing_package",
                    "Playwright is not installed; pip install 'mcp-cyber[phase2]' or playwright",
                    severity="medium",
                )
            ]

        user_sel = str(
            opts.get("playwright_username_selector")
            or 'input[name="username"],input#username,input[type="email"]'
        ).strip()
        pass_sel = str(
            opts.get("playwright_password_selector")
            or 'input[name="password"],input#password,input[type="password"]'
        ).strip()
        sub_sel = str(
            opts.get("playwright_submit_selector") or 'button[type="submit"],input[type="submit"]'
        ).strip()
        wait_ms = int(opts.get("playwright_post_login_wait_ms") or 2000)

        findings: list[RawFinding] = []

        async with async_playwright() as p:
            browser = await p.chromium.launch(headless=True)
            context = await browser.new_context()
            page = await context.new_page()
            await page.goto(login_url, wait_until="domcontentloaded", timeout=60000)
            await page.locator(user_sel).first.fill(username)
            await page.locator(pass_sel).first.fill(password)
            await page.locator(sub_sel).first.click()
            if wait_ms > 0:
                await page.wait_for_timeout(wait_ms)
            try:
                await page.wait_for_load_state("networkidle", timeout=30000)
            except Exception:
                pass

            if opts.get("playwright_screenshot", True) is not False:
                try:
                    png = await page.screenshot(full_page=False)
                    uri = object_store.store_scan_artifact(ctx.scan_id, "session.png", png)
                    findings.append(
                        RawFinding(
                            rule_id="auth.session.screenshot",
                            category="session",
                            title="Post-login screenshot stored (path only; no secrets)",
                            severity="info",
                            confidence=1.0,
                            url=login_url,
                            evidence=[{"type": "screenshot", "storage_uri": uri}],
                            tags=["evidence"],
                        )
                    )
                except Exception as e:
                    log.warning("playwright_screenshot_failed", error=str(e))

            cookies = await context.cookies()
            await browser.close()

        jar = httpx.Cookies()
        for c in cookies:
            domain = c.get("domain") or ""
            path = c.get("path") or "/"
            jar.set(c["name"], c["value"], domain=domain, path=path)

        headers = {"User-Agent": _BASE_UA}
        async with httpx.AsyncClient(headers=headers, cookies=jar, follow_redirects=True) as authed:
            ctx2 = replace(ctx, http_client=authed)
            findings.extend(await HeadersCookiesAdapter().run(ctx2))
        return findings
