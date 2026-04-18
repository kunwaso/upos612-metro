#!/usr/bin/env python3
import asyncio
import json
import sys
from typing import Any, Dict, List


def emit(payload: Dict[str, Any], exit_code: int) -> int:
    sys.stdout.write(json.dumps(payload, ensure_ascii=False))
    sys.stdout.flush()
    return exit_code


def emit_error(code: str, message: str, exit_code: int) -> int:
    return emit(
        {
            "ok": False,
            "error_code": code,
            "message": message,
        },
        exit_code,
    )


def parse_payload(raw_input: str) -> Dict[str, Any]:
    if not raw_input.strip():
        raise ValueError("Request payload is required.")

    return json.loads(raw_input)


def normalize_service_urls(payload: Dict[str, Any]) -> List[str]:
    raw_urls = payload.get("service_urls", [])
    if not isinstance(raw_urls, list):
        return []

    normalized: List[str] = []
    for url in raw_urls:
        clean_url = str(url).strip()
        if clean_url:
            normalized.append(clean_url)

    return normalized


async def translate_text(
    translator_class: Any,
    text: str,
    source_language: str,
    target_language: str,
    translator_kwargs: Dict[str, Any],
) -> Any:
    async with translator_class(**translator_kwargs) as translator:
        return await translator.translate(text, src=source_language, dest=target_language)


def main() -> int:
    try:
        from googletrans import Translator
    except Exception:
        return emit_error(
            "DEPENDENCY_MISSING",
            "The python package 'py-googletrans' is not installed.",
            11,
        )

    try:
        payload = parse_payload(sys.stdin.read())
    except json.JSONDecodeError:
        return emit_error("INVALID_PAYLOAD", "Payload must be valid JSON.", 12)
    except ValueError as exc:
        return emit_error("INVALID_PAYLOAD", str(exc), 12)

    text = str(payload.get("text", "")).strip()
    source_language = str(payload.get("source", "auto")).strip().lower() or "auto"
    target_language = str(payload.get("target", "")).strip().lower()

    if not text:
        return emit_error("INVALID_PAYLOAD", "The 'text' field is required.", 12)

    if not target_language:
        return emit_error("INVALID_PAYLOAD", "The 'target' field is required.", 12)

    service_urls = normalize_service_urls(payload)
    translator_kwargs: Dict[str, Any] = {}
    if service_urls:
        translator_kwargs["service_urls"] = service_urls

    try:
        result = asyncio.run(
            translate_text(
                Translator,
                text,
                source_language,
                target_language,
                translator_kwargs,
            )
        )
    except ValueError as exc:
        return emit_error("UNSUPPORTED_LANGUAGE", str(exc), 13)
    except Exception as exc:
        lower = str(exc).lower()
        if "timeout" in lower or "timed out" in lower:
            return emit_error("TIMEOUT", "Translation request timed out.", 14)
        return emit_error("RUNTIME_FAILURE", "Translation request failed.", 15)

    translated_text = str(getattr(result, "text", "")).strip()
    if not translated_text:
        return emit_error("EMPTY_RESPONSE", "Translation provider returned empty text.", 16)

    return emit({"ok": True, "translated_text": translated_text}, 0)


if __name__ == "__main__":
    sys.exit(main())
