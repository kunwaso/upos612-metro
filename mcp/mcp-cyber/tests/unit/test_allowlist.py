import pytest

from cyber_engine.allowlist import AllowlistError, assert_urls_allowed, url_allowed
from cyber_core.models.scan import AllowlistConfig


def test_allow_same_host():
    assert_urls_allowed(
        ["https://app.example.com/path"],
        "https://app.example.com",
        {"hosts": ["app.example.com"], "path_prefixes": ["/"]},
        require_allowlist=True,
    )


def test_reject_other_host():
    with pytest.raises(AllowlistError):
        assert_urls_allowed(
            ["https://evil.com/x"],
            "https://app.example.com",
            {"hosts": ["app.example.com"]},
            require_allowlist=True,
        )


def test_path_prefix_does_not_match_sibling_prefix() -> None:
    cfg = AllowlistConfig(hosts=["app.example.com"], path_prefixes=["/api"])
    assert url_allowed("https://app.example.com/api/users", "https://app.example.com", cfg)
    assert not url_allowed("https://app.example.com/api-private/admin", "https://app.example.com", cfg)
