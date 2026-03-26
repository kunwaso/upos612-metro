# Contact Feeds Integration

This document explains configuration and provider behavior for the Contact Feeds tab.

## Purpose

The Contact Feeds feature adds a `Feeds` tab on `contacts/{id}` and supports:

1. First-open auto-load from Google when the contact has no saved feed rows
2. Manual refresh for incremental sync and dedupe
3. Google-only related-news cards with image, snippet, source, and article link

## Environment Configuration

Set these keys in `.env`:

1. `GOOGLE_CUSTOM_SEARCH_API_KEY`
2. `GOOGLE_CUSTOM_SEARCH_ENGINE_ID`
3. `GOOGLE_CUSTOM_SEARCH_BASE_URL` (default: `https://www.googleapis.com/customsearch/v1`)
4. `GOOGLE_CUSTOM_SEARCH_DEFAULT_LIMIT` (default: `30`)
5. `GOOGLE_CUSTOM_SEARCH_TIMEOUT` (default: `10`)
6. `CONTACT_FEEDS_DEFAULT_PROVIDER` (default: `google`)
7. `GOOGLE_CUSTOM_SEARCH_VERIFY_SSL` (default: `true`)
8. `GOOGLE_CUSTOM_SEARCH_CA_BUNDLE` (optional path to `cacert.pem`, for example `D:/wamp64/cacert.pem`)
9. `SERPAPI_API_KEY` (optional Google-search fallback provider key)
10. `SERPAPI_BASE_URL` (default: `https://serpapi.com/search`)
11. `SERPAPI_TIMEOUT` (default: `10`)
12. `SERPAPI_VERIFY_SSL` (default: `true`)
13. `SERPAPI_CA_BUNDLE` (optional path to `cacert.pem`)
14. `CONTACT_FEEDS_GOOGLE_FALLBACK_ENABLED` (default: `true`)
15. `CONTACT_FEEDS_GOOGLE_FALLBACK_PROVIDER` (default: `serpapi_google`)

### TLS / cURL 60 on Windows

If you see `cURL error 60: SSL certificate problem`, configure one of:

1. Preferred: set `GOOGLE_CUSTOM_SEARCH_CA_BUNDLE` to a valid CA bundle file (`cacert.pem`) and keep `GOOGLE_CUSTOM_SEARCH_VERIFY_SSL=true`.
2. Local dev fallback only: set `GOOGLE_CUSTOM_SEARCH_VERIFY_SSL=false`.

## Provider Behavior

### Google

Implemented via Google Custom Search JSON API.

Behavior:

1. Searches by the contact company/name and fetches the newest 3 result pages (`start=1,11,21`, up to 30 results).
2. Uses `sort=date` and may add `dateRestrict` as an optimization for refreshes.
3. Extracts title, snippet, source, canonical link, published date, and preview image URL when Google returns one.
4. If Google Custom Search is blocked by credentials, API access, or quota issues, the app can transparently fall back to SerpApi while still storing items under the `google` feed provider key.

### SerpApi Fallback

Optional fallback for when Google Custom Search is unavailable.

Behavior:

1. Uses SerpApi Google news search to fetch up to 30 related results in 3 pages (`start=0,10,20`).
2. Keeps the same normalized record shape as Google Custom Search, including image extraction and canonical link storage.
3. Marks the saved `raw_payload` with `_feed_source=serpapi_google` so fallback-origin records can be distinguished later without changing the user-facing provider.

## Dedupe Strategy

Each feed item is normalized to a canonical URL.

1. Remove common tracking params (`utm_*`, `gclid`, `fbclid`, `mc_*`)
2. Normalize host/scheme/path
3. Hash as `sha256(provider|canonical_url)`
4. Enforce uniqueness by `(business_id, contact_id, provider, url_hash)`
5. During refresh, dated items with `published_at <= latest saved published_at` are skipped before insert; undated items still rely on hash dedupe.

## Security Rules

1. All queries are tenant-scoped by `business_id`.
2. Contact access uses same permission logic as contact view.
3. No external raw HTML scraping.
