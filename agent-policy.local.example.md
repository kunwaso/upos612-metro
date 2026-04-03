# agent-policy.local.md — Machine-Specific Agent Policy

This file is GITIGNORED. Copy it as `agent-policy.local.md` and fill in your machine's specifics.

```
cp agent-policy.local.example.md agent-policy.local.md
```

It is NOT enforced by CI or code. It is a personal note to you (and any AI agent you run locally)
about constraints specific to this machine or environment.

---

## Machine

**Hostname / nickname:** (e.g. "Ray-laptop", "dev-vm-prod-tunnel")

---

## MCP Servers — Never Enable on This Machine

List any MCP servers from the approved list that must NOT be enabled here, and why.

| Server | Reason to disable here |
|--------|------------------------|
| (none by default) | |
| Example: `laravel_mysql` with prod URL | Prod DB tunnel is present; do not enable schema tools that write |

---

## MCP Servers — Extra Servers Available Here Only

If you have a machine-local MCP server not listed in the team policy, note it here.
Do NOT add it to shared `.cursor/mcp.json` or commit it.

| Server | Config key | Purpose | Approved? |
|--------|-----------|---------|-----------|
| — | | | |

---

## Database / Environment Notes

- Is a production database accessible from this machine? (yes/no)
  - If yes: **do not** point `laravel_mysql` at it. Use a read-only replica or local dev DB only.
- Local dev DB name / port: (e.g. `upos_dev` on port 3306)
- Staging DB: (accessible / not accessible)

---

## Data Handling Reminders (personal)

- [ ] My `.cursor/mcp.json` points only to local/staging DBs
- [ ] My `~/.codex/config.toml` has no production credentials
- [ ] I do not paste real customer data into AI prompts
- [ ] I run `php scripts/check-mcp-health.php` before long agent sessions

---

## Other Notes

(free-form: VPN requirements, special PHP version, Windows vs WSL quirks, etc.)
