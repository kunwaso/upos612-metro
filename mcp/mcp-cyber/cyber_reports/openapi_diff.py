"""Structural diff between two OpenAPI/Swagger JSON documents (Phase 2 gap)."""

from __future__ import annotations

import json
from typing import Any


def _iter_operations(paths: dict[str, Any]) -> set[tuple[str, str]]:
    """Return set of (METHOD, path) in uppercase method."""
    out: set[tuple[str, str]] = set()
    for path_key, path_item in (paths or {}).items():
        if not isinstance(path_item, dict):
            continue
        for method, op in path_item.items():
            m = method.upper()
            if m not in (
                "GET",
                "PUT",
                "POST",
                "DELETE",
                "OPTIONS",
                "HEAD",
                "PATCH",
                "TRACE",
            ):
                continue
            if not isinstance(op, dict):
                continue
            out.add((m, path_key))
    return out


def diff_openapi_dicts(base: dict[str, Any], target: dict[str, Any]) -> dict[str, Any]:
    paths_a = set((base.get("paths") or {}).keys()) if isinstance(base.get("paths"), dict) else set()
    paths_b = set((target.get("paths") or {}).keys()) if isinstance(target.get("paths"), dict) else set()
    ops_a = _iter_operations(base.get("paths") or {})
    ops_b = _iter_operations(target.get("paths") or {})

    paths_added = sorted(paths_b - paths_a)
    paths_removed = sorted(paths_a - paths_b)
    ops_added = [{"method": m, "path": p} for m, p in sorted(ops_b - ops_a)]
    ops_removed = [{"method": m, "path": p} for m, p in sorted(ops_a - ops_b)]

    def _comp_keys(doc: dict[str, Any], key: str) -> list[str]:
        comp = doc.get("components") or {}
        if not isinstance(comp, dict):
            return []
        block = comp.get(key) or {}
        if not isinstance(block, dict):
            return []
        return sorted(block.keys())

    schemas_a = set(_comp_keys(base, "schemas"))
    schemas_b = set(_comp_keys(target, "schemas"))
    schemas_added = sorted(schemas_b - schemas_a)
    schemas_removed = sorted(schemas_a - schemas_b)

    return {
        "paths_added": paths_added,
        "paths_removed": paths_removed,
        "operations_added": ops_added,
        "operations_removed": ops_removed,
        "components_schemas_added": schemas_added,
        "components_schemas_removed": schemas_removed,
        "summary": {
            "paths_added_count": len(paths_added),
            "paths_removed_count": len(paths_removed),
            "operations_added_count": len(ops_added),
            "operations_removed_count": len(ops_removed),
        },
    }


def load_openapi_json(text: str) -> dict[str, Any]:
    data = json.loads(text)
    if not isinstance(data, dict):
        raise ValueError("OpenAPI document must be a JSON object")
    return data
