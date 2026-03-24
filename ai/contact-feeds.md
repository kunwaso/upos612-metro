# Contact Feeds Integration

This document explains configuration and provider behavior for the Contact Feeds tab.

## Purpose

The Contact Feeds feature adds a `Feeds` tab on `contacts/{id}` and supports:

1. `Load News` (seed once from provider if DB is empty)
2. `Update Feed` (incremental sync and dedupe)
3. Provider selection (`google`, `facebook`, `linkedin`)

## Environment Configuration

Set these keys in `.env`:

1. `GOOGLE_CUSTOM_SEARCH_API_KEY`
2. `GOOGLE_CUSTOM_SEARCH_ENGINE_ID`
3. `GOOGLE_CUSTOM_SEARCH_BASE_URL` (default: `https://www.googleapis.com/customsearch/v1`)
4. `GOOGLE_CUSTOM_SEARCH_DEFAULT_LIMIT` (default: `20`)
5. `GOOGLE_CUSTOM_SEARCH_TIMEOUT` (default: `10`)
6. `CONTACT_FEEDS_DEFAULT_PROVIDER` (default: `google`)

## Provider Behavior

### Google

Implemented via Google Custom Search JSON API.

### Facebook and LinkedIn

Current status: placeholder providers.

Behavior:

1. Return controlled "not configured" error.
2. No scraping logic is used.
3. Expansion should be done only through official APIs.

## Dedupe Strategy

Each feed item is normalized to a canonical URL.

1. Remove common tracking params (`utm_*`, `gclid`, `fbclid`, `mc_*`)
2. Normalize host/scheme/path
3. Hash as `sha256(provider|canonical_url)`
4. Enforce uniqueness by `(business_id, contact_id, provider, url_hash)`

## Security Rules

1. All queries are tenant-scoped by `business_id`.
2. Contact access uses same permission logic as contact view.
3. No external raw HTML scraping.
