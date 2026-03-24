<?php

namespace App\Utils;

use App\Contact;
use App\ContactFeed;
use Illuminate\Support\Carbon;

class ContactFeedUtil extends Util
{
    /**
     * @var array<string, \App\Utils\ContactFeedProviderInterface>
     */
    protected $providers = [];

    /**
     * @param \App\Utils\GoogleContactFeedProvider $googleProvider
     * @param \App\Utils\FacebookContactFeedProvider $facebookProvider
     * @param \App\Utils\LinkedInContactFeedProvider $linkedInProvider
     */
    public function __construct(
        GoogleContactFeedProvider $googleProvider,
        FacebookContactFeedProvider $facebookProvider,
        LinkedInContactFeedProvider $linkedInProvider
    ) {
        $this->providers = [
            'google' => $googleProvider,
            'facebook' => $facebookProvider,
            'linkedin' => $linkedInProvider,
        ];
    }

    /**
     * Register or override a provider implementation.
     *
     * @param string $provider
     * @param \App\Utils\ContactFeedProviderInterface $implementation
     * @return void
     */
    public function setProvider($provider, ContactFeedProviderInterface $implementation)
    {
        $this->providers[$provider] = $implementation;
    }

    /**
     * Return allowed provider list.
     *
     * @return array<int, string>
     */
    public function getAllowedProviders()
    {
        $providers = config('services.contact_feeds.providers', ['google', 'facebook', 'linkedin']);

        return is_array($providers) ? $providers : ['google', 'facebook', 'linkedin'];
    }

    /**
     * Normalize and validate provider.
     *
     * @param string|null $provider
     * @return string
     */
    public function normalizeProvider($provider = null)
    {
        $default = (string) config('services.contact_feeds.default_provider', 'google');
        $normalized = strtolower(trim((string) ($provider ?: $default)));

        if (! in_array($normalized, $this->getAllowedProviders(), true)) {
            throw new \InvalidArgumentException('Unsupported provider selected.');
        }

        return $normalized;
    }

    /**
     * Normalize page size.
     *
     * @param mixed $limit
     * @return int
     */
    public function normalizeLimit($limit)
    {
        $default_limit = (int) config('services.google_custom_search.default_limit', 20);
        $normalized = is_numeric($limit) ? (int) $limit : $default_limit;

        return max(1, min($normalized, 50));
    }

    /**
     * Return latest saved feed records for a contact/provider.
     *
     * @param int $business_id
     * @param int $contact_id
     * @param string $provider
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getFeedsForContact($business_id, $contact_id, $provider, $limit = 20)
    {
        $provider = $this->normalizeProvider($provider);
        $limit = $this->normalizeLimit($limit);

        return ContactFeed::forContact($business_id, $contact_id)
            ->where('provider', $provider)
            ->orderByRaw('COALESCE(published_at, fetched_at) DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * "Load News" behavior:
     * - if records already exist => skip provider call
     * - else => sync from provider once
     *
     * @param int $business_id
     * @param \App\Contact $contact
     * @param array $options
     * @return array<string, mixed>
     */
    public function loadFeeds($business_id, Contact $contact, array $options = [])
    {
        $provider = $this->normalizeProvider($options['provider'] ?? null);
        $limit = $this->normalizeLimit($options['limit'] ?? null);
        $existing_query = ContactFeed::forContact($business_id, $contact->id)->where('provider', $provider);
        $existing_count = (int) $existing_query->count();

        if ($existing_count > 0) {
            $last_synced_at = $existing_query->max('fetched_at');

            return [
                'success' => true,
                'msg' => 'Existing feed found. Loaded from database without searching again.',
                'inserted_count' => 0,
                'skipped_count' => 0,
                'existing_count' => $existing_count,
                'provider' => $provider,
                'last_synced_at' => ! empty($last_synced_at) ? Carbon::parse($last_synced_at)->toDateTimeString() : null,
            ];
        }

        return $this->syncFeeds($business_id, $contact, $provider, $limit, false);
    }

    /**
     * "Update Feed" behavior:
     * - always call provider for latest results
     * - skip existing records by URL hash
     *
     * @param int $business_id
     * @param \App\Contact $contact
     * @param array $options
     * @return array<string, mixed>
     */
    public function updateFeeds($business_id, Contact $contact, array $options = [])
    {
        $provider = $this->normalizeProvider($options['provider'] ?? null);
        $limit = $this->normalizeLimit($options['limit'] ?? null);

        return $this->syncFeeds($business_id, $contact, $provider, $limit, true);
    }

    /**
     * Sync data from provider and persist deduplicated rows.
     *
     * @param int $business_id
     * @param \App\Contact $contact
     * @param string $provider
     * @param int $limit
     * @param bool $incremental
     * @return array<string, mixed>
     */
    protected function syncFeeds($business_id, Contact $contact, $provider, $limit, $incremental)
    {
        $existing_query = ContactFeed::forContact($business_id, $contact->id)->where('provider', $provider);
        $existing_count_before = (int) $existing_query->count();
        $options = [
            'provider' => $provider,
            'limit' => $limit,
        ];

        if ($incremental) {
            $latest_published_at = $existing_query->max('published_at');
            if (! empty($latest_published_at)) {
                $options['published_after'] = Carbon::parse($latest_published_at)->toDateTimeString();
            }
        }

        try {
            $provider_impl = $this->resolveProvider($provider);
            $items = $provider_impl->search($contact, $options);
            $persisted = $this->persistItems($business_id, $contact->id, $provider, $items);
        } catch (\Throwable $e) {
            $last_synced_at = $existing_query->max('fetched_at');

            return [
                'success' => false,
                'msg' => $e->getMessage(),
                'inserted_count' => 0,
                'skipped_count' => 0,
                'existing_count' => $existing_count_before,
                'provider' => $provider,
                'last_synced_at' => ! empty($last_synced_at) ? Carbon::parse($last_synced_at)->toDateTimeString() : null,
            ];
        }

        $existing_count_after = (int) ContactFeed::forContact($business_id, $contact->id)
            ->where('provider', $provider)
            ->count();
        $last_synced_at = ContactFeed::forContact($business_id, $contact->id)
            ->where('provider', $provider)
            ->max('fetched_at');
        $inserted = (int) $persisted['inserted_count'];
        $skipped = (int) $persisted['skipped_count'];

        if ($inserted > 0) {
            $msg = $incremental
                ? 'Feed updated successfully with latest news.'
                : 'News loaded successfully from provider.';
        } else {
            $msg = 'No new feed items were found.';
        }

        return [
            'success' => true,
            'msg' => $msg,
            'inserted_count' => $inserted,
            'skipped_count' => $skipped,
            'existing_count' => $existing_count_after,
            'provider' => $provider,
            'last_synced_at' => ! empty($last_synced_at) ? Carbon::parse($last_synced_at)->toDateTimeString() : null,
        ];
    }

    /**
     * Persist provider items with dedupe by URL hash.
     *
     * @param int $business_id
     * @param int $contact_id
     * @param string $provider
     * @param array $items
     * @return array{inserted_count:int, skipped_count:int}
     */
    protected function persistItems($business_id, $contact_id, $provider, array $items)
    {
        if (empty($items)) {
            return ['inserted_count' => 0, 'skipped_count' => 0];
        }

        $now = now();
        $candidate_rows = [];
        $candidate_hashes = [];

        foreach ($items as $item) {
            $canonical_url = $this->canonicalizeUrl((string) ($item['canonical_url'] ?? ''));
            if (empty($canonical_url)) {
                continue;
            }

            $hash = $this->hashUrl($provider, $canonical_url);
            $published_at = $this->parseDate($item['published_at'] ?? null);

            $candidate_rows[] = [
                'business_id' => $business_id,
                'contact_id' => $contact_id,
                'provider' => $provider,
                'title' => mb_substr((string) ($item['title'] ?? ''), 0, 512),
                'snippet' => ! empty($item['snippet']) ? (string) $item['snippet'] : null,
                'canonical_url' => $canonical_url,
                'url_hash' => $hash,
                'source_name' => ! empty($item['source_name']) ? (string) $item['source_name'] : null,
                'published_at' => $published_at,
                'fetched_at' => $now,
                'raw_payload' => json_encode($item['raw_payload'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $candidate_hashes[] = $hash;
        }

        if (empty($candidate_rows)) {
            return ['inserted_count' => 0, 'skipped_count' => count($items)];
        }

        $existing_hashes = ContactFeed::forContact($business_id, $contact_id)
            ->where('provider', $provider)
            ->whereIn('url_hash', array_values(array_unique($candidate_hashes)))
            ->pluck('url_hash')
            ->all();
        $existing_lookup = array_fill_keys($existing_hashes, true);
        $seen_hashes = [];
        $rows_to_insert = [];
        $skipped_count = 0;

        foreach ($candidate_rows as $row) {
            if (isset($seen_hashes[$row['url_hash']]) || isset($existing_lookup[$row['url_hash']])) {
                $skipped_count++;
                continue;
            }

            $seen_hashes[$row['url_hash']] = true;
            $rows_to_insert[] = $row;
        }

        if (! empty($rows_to_insert)) {
            ContactFeed::upsert(
                $rows_to_insert,
                ['business_id', 'contact_id', 'provider', 'url_hash'],
                ['title', 'snippet', 'source_name', 'published_at', 'fetched_at', 'raw_payload', 'updated_at']
            );
        }

        return [
            'inserted_count' => count($rows_to_insert),
            'skipped_count' => $skipped_count,
        ];
    }

    /**
     * Resolve provider implementation by key.
     *
     * @param string $provider
     * @return \App\Utils\ContactFeedProviderInterface
     */
    protected function resolveProvider($provider)
    {
        if (empty($this->providers[$provider])) {
            throw new \RuntimeException('Provider is not registered.');
        }

        return $this->providers[$provider];
    }

    /**
     * Normalize URL and strip common tracking parameters.
     *
     * @param string $url
     * @return string|null
     */
    public function canonicalizeUrl($url)
    {
        $url = trim((string) $url);
        if (empty($url)) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $scheme = ! empty($parts['scheme']) ? strtolower($parts['scheme']) : 'https';
        $host = strtolower($parts['host']);
        $port = ! empty($parts['port']) ? ':'.$parts['port'] : '';
        $path = ! empty($parts['path']) ? preg_replace('#/+#', '/', $parts['path']) : '/';

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $tracking_keys = [
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'gclid', 'fbclid', 'mc_cid', 'mc_eid',
            ];

            foreach ($tracking_keys as $tracking_key) {
                unset($query[$tracking_key]);
            }
            ksort($query);
        }

        $normalized = $scheme.'://'.$host.$port.$path;
        if (! empty($query)) {
            $normalized .= '?'.http_build_query($query);
        }

        return $normalized;
    }

    /**
     * Build deterministic URL hash for dedupe.
     *
     * @param string $provider
     * @param string $canonical_url
     * @return string
     */
    public function hashUrl($provider, $canonical_url)
    {
        return hash('sha256', strtolower($provider.'|'.$canonical_url));
    }

    /**
     * Convert supported date inputs to DB date string.
     *
     * @param mixed $date
     * @return string|null
     */
    protected function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
