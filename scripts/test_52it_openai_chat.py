#!/usr/bin/env python3
"""
Smoke test for an OpenAI-compatible API (e.g. https://api.52it.de/v1).

Uses only the standard library for the HTTP call. Optionally loads a .env file
if python-dotenv is installed.

Environment (API key required — use one of):
  IT52_API_KEY, XOE_api_key, XOE_API_KEY, OPENAI_API_KEY

Environment (optional):
  IT52_API_BASE or host — base URL, default https://api.52it.de/v1
  IT52_MODEL or model — comma-separated model ids; each is tried in order

CLI:
  --models a,b,c     overrides env model list
  --first-only       stop after the first model (success or failure)
  --protocol chat    OpenAI-style POST .../v1/chat/completions (default)
  --protocol messages Anthropic-style POST .../v1/messages
  --protocol both    run chat then messages for each model

GPT-5.4-style POST .../v1/responses is not implemented here (different JSON shape).

Examples:
  python scripts/test_52it_openai_chat.py --dotenv .env
  python scripts/test_52it_openai_chat.py --dotenv .env --protocol messages
  python scripts/test_52it_openai_chat.py --dotenv .env --protocol both --models claude-opus-4.6
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import urllib.error
import urllib.request
from typing import List, Optional, Tuple

DEFAULT_MODELS: List[str] = [
    "claude-sonnet-4.6",
    "claude-opus-4.6",
    "claude-opus-4.5",
    "claude-sonnet-4.5",
]


def load_dotenv_optional(path: Optional[str]) -> None:
    if not path:
        return
    try:
        from dotenv import load_dotenv  # type: ignore
    except ImportError:
        print("warning: pip install python-dotenv to use --dotenv", file=sys.stderr)
        return
    load_dotenv(path)


def try_load_default_dotenv() -> None:
    try:
        from dotenv import load_dotenv  # type: ignore
    except ImportError:
        return
    load_dotenv()


def getenv_first(*names: str) -> str:
    for name in names:
        raw = os.environ.get(name)
        if raw is not None and str(raw).strip() != "":
            return str(raw).strip()
    return ""


def safe_print(text: str, limit: int = 8000) -> None:
    chunk = text[:limit]
    try:
        print(chunk)
    except UnicodeEncodeError:
        print(chunk.encode("utf-8", errors="replace").decode("utf-8", errors="replace"))


def parse_model_list(arg_models: Optional[str], env_models: str) -> List[str]:
    if arg_models is not None and str(arg_models).strip() != "":
        raw = str(arg_models).strip()
    elif env_models.strip() != "":
        raw = env_models.strip()
    else:
        return list(DEFAULT_MODELS)

    out = [m.strip() for m in raw.split(",") if m.strip()]
    return out if out else list(DEFAULT_MODELS)


def try_chat_completion(
    base: str, api_key: str, model: str
) -> Tuple[bool, int, str]:
    """
    POST /chat/completions for one model.
    Returns (ok, http_status, message) where message is assistant text or error body/summary.
    """
    url = f"{base}/chat/completions"
    payload = {
        "model": model,
        "messages": [{"role": "user", "content": "Reply with exactly: OK"}],
        "max_tokens": 32,
    }
    data = json.dumps(payload).encode("utf-8")
    request = urllib.request.Request(
        url,
        data=data,
        method="POST",
        headers={
            "Authorization": "Bearer " + api_key,
            "Content-Type": "application/json",
            "User-Agent": "Mozilla/5.0 (compatible; UPOS-api-test/1.0)",
        },
    )

    try:
        with urllib.request.urlopen(request, timeout=120) as response:
            status = response.status
            body = response.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as exc:
        body = exc.read().decode("utf-8", errors="replace")
        return False, exc.code, body
    except urllib.error.URLError as exc:
        return False, 0, str(exc.reason)

    if status != 200:
        return False, status, body

    try:
        doc = json.loads(body)
    except json.JSONDecodeError:
        return False, status, body[:4000]

    content = (
        doc.get("choices", [{}])[0]
        .get("message", {})
        .get("content", "")
    )
    if content:
        return True, status, str(content).strip()[:2000]
    return True, status, json.dumps(doc, indent=2)[:4000]


def try_anthropic_messages(
    base: str, api_key: str, model: str
) -> Tuple[bool, int, str]:
    """
    POST /messages (Anthropic-style). Many gateways still accept Bearer auth.
    """
    url = f"{base}/messages"
    payload = {
        "model": model,
        "max_tokens": 32,
        "messages": [{"role": "user", "content": "Reply with exactly: OK"}],
    }
    data = json.dumps(payload).encode("utf-8")
    request = urllib.request.Request(
        url,
        data=data,
        method="POST",
        headers={
            "Authorization": "Bearer " + api_key,
            "Content-Type": "application/json",
            "anthropic-version": "2023-06-01",
            "User-Agent": "Mozilla/5.0 (compatible; UPOS-api-test/1.0)",
        },
    )

    try:
        with urllib.request.urlopen(request, timeout=120) as response:
            status = response.status
            body = response.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as exc:
        body = exc.read().decode("utf-8", errors="replace")
        return False, exc.code, body
    except urllib.error.URLError as exc:
        return False, 0, str(exc.reason)

    if status != 200:
        return False, status, body

    try:
        doc = json.loads(body)
    except json.JSONDecodeError:
        return False, status, body[:4000]

    parts: List[str] = []
    for block in doc.get("content") or []:
        if isinstance(block, dict) and block.get("type") == "text":
            parts.append(str(block.get("text", "")))
    text = "".join(parts).strip()
    if text:
        return True, status, text[:2000]
    return True, status, json.dumps(doc, indent=2)[:4000]


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        try:
            sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        except (AttributeError, OSError, ValueError):
            pass

    parser = argparse.ArgumentParser(
        description="POST /chat/completions to test OpenAI-compatible APIs."
    )
    parser.add_argument(
        "--dotenv",
        metavar="PATH",
        help="Load variables from this .env file (requires: pip install python-dotenv)",
    )
    parser.add_argument(
        "--models",
        metavar="LIST",
        help="Comma-separated model ids (overrides IT52_MODEL / model from env)",
    )
    parser.add_argument(
        "--first-only",
        action="store_true",
        help="Only try the first model in the list",
    )
    parser.add_argument(
        "--protocol",
        choices=["chat", "messages", "both"],
        default="chat",
        help="API shape: OpenAI chat.completions, Anthropic messages, or both",
    )
    args = parser.parse_args()

    load_dotenv_optional(args.dotenv)
    if not args.dotenv:
        try_load_default_dotenv()

    base = getenv_first("IT52_API_BASE", "host") or "https://api.52it.de/v1"
    base = base.rstrip("/")
    api_key = getenv_first(
        "IT52_API_KEY", "XOE_api_key", "XOE_API_KEY", "OPENAI_API_KEY"
    )
    env_model_line = getenv_first("IT52_MODEL", "model")

    models = parse_model_list(args.models, env_model_line)
    if args.first_only:
        models = models[:1]

    if not api_key:
        print(
            "error: set IT52_API_KEY, XOE_api_key, XOE_API_KEY, or OPENAI_API_KEY",
            file=sys.stderr,
        )
        return 1

    print("Base:", base)
    print("Protocol:", args.protocol)
    print("Trying", len(models), "model(s):", ", ".join(models))
    print()

    any_ok = False
    for model in models:
        print("===", model, "===")

        if args.protocol in ("chat", "both"):
            print("--- chat/completions ---")
            print("POST", f"{base}/chat/completions")
            ok, status, msg = try_chat_completion(base, api_key, model)
            if ok:
                print("HTTP", status, "OK")
                print("assistant:", msg)
                any_ok = True
            else:
                label = "HTTP" if status else "error"
                print(label, status if status else "", "FAIL")
                safe_print(msg)
            print()

        if args.protocol in ("messages", "both"):
            print("--- messages (Anthropic) ---")
            print("POST", f"{base}/messages")
            ok, status, msg = try_anthropic_messages(base, api_key, model)
            if ok:
                print("HTTP", status, "OK")
                print("assistant:", msg)
                any_ok = True
            else:
                label = "HTTP" if status else "error"
                print(label, status if status else "", "FAIL")
                safe_print(msg)
            print()

        if args.first_only:
            break

    if any_ok:
        print("Summary: at least one model succeeded.")
        return 0

    print("Summary: all models failed.")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
