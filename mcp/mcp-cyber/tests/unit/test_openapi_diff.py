from cyber_reports.openapi_diff import diff_openapi_dicts, load_openapi_json


def test_diff_paths_and_operations() -> None:
    a = {
        "openapi": "3.0.0",
        "paths": {
            "/a": {"get": {"responses": {"200": {"description": "ok"}}}},
            "/b": {"post": {"responses": {"200": {"description": "ok"}}}},
        },
    }
    b = {
        "openapi": "3.0.0",
        "paths": {
            "/a": {"get": {"responses": {"200": {"description": "ok"}}}},
            "/c": {"get": {"responses": {"200": {"description": "ok"}}}},
        },
    }
    d = diff_openapi_dicts(a, b)
    assert "/c" in d["paths_added"]
    assert "/b" in d["paths_removed"]
    assert any(x["path"] == "/c" and x["method"] == "GET" for x in d["operations_added"])
    assert any(x["path"] == "/b" and x["method"] == "POST" for x in d["operations_removed"])


def test_components_schemas_diff() -> None:
    a = {"paths": {}, "components": {"schemas": {"Foo": {}, "Bar": {}}}}
    b = {"paths": {}, "components": {"schemas": {"Foo": {}, "Baz": {}}}}
    d = diff_openapi_dicts(a, b)
    assert "Baz" in d["components_schemas_added"]
    assert "Bar" in d["components_schemas_removed"]


def test_load_openapi_json() -> None:
    doc = load_openapi_json('{"openapi":"3.0.0","paths":{}}')
    assert doc["openapi"] == "3.0.0"
