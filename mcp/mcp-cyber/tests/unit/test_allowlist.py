import pytest

from cyber_engine.allowlist import AllowlistError, assert_urls_allowed


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
