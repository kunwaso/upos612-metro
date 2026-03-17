# SECURITY

## Safety Model

- Default mode is `SAFE`.
- `apply_patch` is available only when `LARAVEL_MCP_MODE=PATCH`.
- Patch operations use `git apply --check` before apply.

## Path Restrictions

Blocked targets include:

- `.git/`
- `vendor/`
- `storage/`
- `.env`
- `.env.*`
- key/cert file extensions: `.pem`, `.key`, `.p12`, `.crt`

Any patch touching denied paths is rejected.

## Secret Redaction

`list_env` and `config_snapshot` are allowlist-only.

Examples of excluded secrets:

- `APP_KEY`
- `DB_PASSWORD`
- tokens and secret keys

## SQL Guardrails

`explain_query` accepts only a single `SELECT`/CTE statement.

Blocked for EXPLAIN:

- `INSERT`, `UPDATE`, `DELETE`
- DDL (`CREATE`, `ALTER`, `DROP`, `TRUNCATE`)
- privilege operations (`GRANT`, `REVOKE`)

## Notes

- This server is intended for local development use.
- Do not run with elevated privileges against production systems.
