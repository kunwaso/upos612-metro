#!/usr/bin/env python3
"""Local Hugging Face embedding worker for semantic-code-search-mcp."""

from __future__ import annotations

import json
import os
import sys
from typing import Any


def read_bool(name: str, default: bool) -> bool:
    raw = os.getenv(name)
    if raw is None or raw == "":
        return default
    return raw.strip().lower() in {"1", "true", "yes", "on"}


def read_int(name: str, default: int, minimum: int = 1, maximum: int = 4096) -> int:
    raw = os.getenv(name)
    if raw is None or raw == "":
        return default

    try:
        value = int(raw)
    except ValueError:
        return default

    return max(minimum, min(maximum, value))


def resolve_device(mode: str) -> str:
    mode = mode.strip().lower()
    if mode and mode != "auto":
        return mode

    try:
        import torch  # type: ignore
    except Exception:
        return "cpu"

    if torch.cuda.is_available():
        return "cuda"

    mps_backend = getattr(torch.backends, "mps", None)
    if mps_backend is not None and mps_backend.is_available():
        return "mps"

    return "cpu"


def emit(payload: dict[str, Any]) -> None:
    sys.stdout.write(json.dumps(payload) + "\n")
    sys.stdout.flush()


def main() -> int:
    os.environ.setdefault("TOKENIZERS_PARALLELISM", "false")

    model_name = os.getenv("MCP_SEMANTIC_EMBED_MODEL", "BAAI/bge-base-en")
    batch_size = read_int("MCP_SEMANTIC_HF_BATCH_SIZE", 24, 1, 256)
    max_length = read_int("MCP_SEMANTIC_HF_MAX_LENGTH", 512, 64, 8192)
    normalize = read_bool("MCP_SEMANTIC_HF_NORMALIZE", True)
    local_files_only = read_bool("MCP_SEMANTIC_HF_LOCAL_FILES_ONLY", True)
    query_instruction = os.getenv(
        "MCP_SEMANTIC_HF_QUERY_INSTRUCTION",
        "Represent this sentence for searching relevant passages: ",
    )
    device = resolve_device(os.getenv("MCP_SEMANTIC_HF_DEVICE", "auto"))

    try:
        from sentence_transformers import SentenceTransformer  # type: ignore
    except Exception as exc:
        emit(
            {
                "ok": False,
                "error": (
                    "Missing Python dependencies for local Hugging Face embeddings. "
                    "Install sentence-transformers and torch. "
                    f"Detail: {exc}"
                ),
            }
        )
        return 1

    try:
        model = SentenceTransformer(
            model_name,
            device=device,
            local_files_only=local_files_only,
        )
        model.max_seq_length = max_length
    except Exception as exc:
        emit(
            {
                "ok": False,
                "error": (
                    f"Unable to load local Hugging Face model '{model_name}'. "
                    "Ensure it exists in local cache or disable offline mode. "
                    f"Detail: {exc}"
                ),
            }
        )
        return 1

    emit({"ok": True, "event": "ready", "model": model_name, "device": device})

    for line in sys.stdin:
        raw = line.strip()
        if raw == "":
            continue

        try:
            request = json.loads(raw)
        except json.JSONDecodeError:
            emit({"ok": False, "error": "Invalid JSON payload."})
            continue

        request_type = request.get("type")
        if request_type == "shutdown":
            emit({"ok": True, "event": "shutdown"})
            return 0

        if request_type != "embed":
            emit({"ok": False, "error": "Unsupported request type."})
            continue

        texts = request.get("texts")
        if not isinstance(texts, list) or any(not isinstance(item, str) for item in texts):
            emit({"ok": False, "error": "texts must be an array of strings."})
            continue

        task = request.get("task", "document")
        if task == "query" and query_instruction:
            texts = [f"{query_instruction}{item}" for item in texts]

        try:
            embeddings = model.encode(
                texts,
                batch_size=batch_size,
                show_progress_bar=False,
                normalize_embeddings=normalize,
                convert_to_numpy=True,
            ).tolist()
        except Exception as exc:
            emit({"ok": False, "error": f"Embedding failed: {exc}"})
            continue

        emit({"ok": True, "embeddings": embeddings})

    return 0


if __name__ == "__main__":
    raise SystemExit(main())

