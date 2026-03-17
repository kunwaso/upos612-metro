---
name: cursor-tool-behavior
description: Follow Cursor-style tool selection for reading files, searching codebases, and doing multi-step discovery. Use when Cursor or Codex needs to inspect workspace code, choose between read_file, grep, semantic search, MCP tools, or terminal commands, and avoid shell file reads or repeated find_symbols calls.
---

# Cursor Tool Behavior

## Quick Rules

- Prefer grep for exact strings, identifiers, and routes; use semantic search only when the exact symbol is unknown.
- Search first, then read only the needed files or line ranges.
- Use `read_file` or `read_file_cache` with `offset` and `limit` for file content; never use shell reads.
- When searches or reads are independent, issue them in the same turn.
- Use `find_symbols` for structured symbol filtering, not for simple "where is string X?" lookups.

## File reads

- Use `read_file(path, offset?, limit?)` for workspace text-file reads when `read_file_cache` is available.
- When `read_file_cache` is not available, use the platform `read_file(path, offset?, limit?)` tool with `offset` and `limit` when supported instead of shell reads.
- Use `offset` plus `limit` for line slices instead of shell-based slicing.
- Do not use `Get-Content`, `cat`, or `rg` to read file contents when `read_file_cache` can serve the read.
- Do not use shell commands to read file content even when `read_file_cache` is unavailable; fall back to the platform read tool instead.
- **Never run in the shell:** `Get-Content -Path <file> | Select-Object -First N`, `Get-Content ...; $c[205..255]`, or any PowerShell/pipe slice to read a file or line range - use the read_file tool with offset/limit instead.

## Search and discovery

- Prefer the **grep MCP** `grep` tool for repo-wide exact/pattern search when it is available.
- In Cursor, if grep MCP is not configured, use Cursor's built-in Grep instead of running `rg` or `grep` in the shell.
- Prefer the **semantic code search MCP** `search_code` tool for behavior-level discovery (for example "where is X done?") when it is available.
- Use grep for exact strings, identifiers, routes, selectors, and translation keys; use semantic or codebase search for behavior-level discovery when the exact symbol is unknown.
- Narrow with grep or semantic search first, then read only the narrowed files or line ranges.
- Use the **grep tool** (the tool call) for pattern search; do not run `rg` or `grep` in the shell.
- **Never run in the shell:** `rg -n "pattern"`, `grep -n "pattern"`, or similar - use the platform grep/search tool call instead.
- Use grep or the platform equivalent for exact strings, identifiers, routes, selectors, and translation keys.
- Use semantic or codebase search for behavior-level discovery when the exact symbol is unknown.
- Use `find_symbols` only when structured symbol filtering or symbol-type context is required.
- Do not use multiple `find_symbols` calls for simple "where is string X?" discovery; use one grep or semantic search to locate the file, then read it once with `read_file`.
- After locating the file, switch to `read_file` instead of repeating `find_symbols` or shell reads for more context.

## Edit and write

- Use search-replace (or the platform edit tool) for targeted edits; use write for new files or full-file replacement; use delete only when the task requires removing a file.

## Run and verify

- Use terminal/run command for tests, artisan, composer, migrations, and git.
- Use read lints (or diagnostics) after edits to catch syntax and style issues.

## Other tools

- Use file-find or glob to locate files by name or pattern.
- Use plan or todo tools before non-trivial changes so multi-step work stays explicit.
- Use web search or fetch-URL for stable external documentation instead of guessing.
- Use subagents/task (explore, shell, generalPurpose) to delegate discovery or shell work with a clear spec and return format.
- Use generate image only when the user explicitly asks for an image.
- Use edit notebook only for Jupyter notebook cells; use update memory only when the platform supports it and the information is worth persisting.

## Analysis / scan workflow

For tasks like **auditing a module**, **cloning/porting code**, or **understanding a codebase** (e.g. clone Aichat end-to-end):

1. **Grep (or glob) first** — List files in the area, then grep for patterns (e.g. `projectx|fabric|business_id`) to get file paths and line numbers. Do not read every file in full yet.
2. **Targeted reads** — Read only the files or line ranges that grep flagged. Use `offset` + `limit` for large files. Read 3–5 independent files **in parallel** per turn.
3. **Full-file read only when needed** — Use a full-file read when you are about to edit that file or need the whole unit. For "does this file reference X?" use grep; for "what does this method do?" read the method's range.
4. **No shell reads** — Use the platform read tool (or `read_file_cache`) with offset/limit instead of `Get-Content` or shell line-range hacks.

Pattern: **grep → targeted/parallel reads → full read only when editing**. See `ai/agent-tools-and-mcp.md` §2.8.

## Habits

- When multiple files or patterns can be searched or read independently, issue those tool calls in the same turn.
- Run independent reads, greps, and searches in parallel when they do not depend on each other.
- Read a located file once with `read_file` and request additional slices only when more context is needed.
- Keep tool use evidence-driven: search first, read second, then edit or execute.
- Avoid repeated shell reads or repeated `find_symbols` calls for the same file once it has been located.
