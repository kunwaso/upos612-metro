<?php

namespace App\Utils;

use App\Contact;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class GoogleContactFeedProvider implements ContactFeedProviderInterface
{
    /**
     * Search Google Custom Search and return normalized items.
     *
     * @param \App\Contact $contact
     * @param array $options
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException
     */
    public function search(Contact $contact, array $options = [])
    {
        $api_key = (string) config('services.google_custom_search.api_key');
        $search_engine_id = (string) config('services.google_custom_search.search_engine_id');
        $base_url = (string) config('services.google_custom_search.base_url', 'https://www.googleapis.com/customsearch/v1');
        $timeout = (int) config('services.google_custom_search.timeout', 10);

        if (empty($api_key) || empty($search_engine_id)) {
            throw new \RuntimeException('Google Custom Search credentials are not configured.');
        }

        $limit = isset($options['limit']) ? (int) $options['limit'] : (int) config('services.google_custom_search.default_limit', 20);
        $limit = max(1, min($limit, 50));

        $query = $this->buildQuery($contact, $options);
        $items = [];
        $remaining = $limit;
        $start = 1;

        while ($remaining > 0 && $start <= 91) {
            $batch_size = min(10, $remaining);
            $params = [
                'key' => $api_key,
                'cx' => $search_engine_id,
                'q' => $query,
                'num' => $batch_size,
                'start' => $start,
                'sort' => 'date',
            ];

            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($base_url, $params);

            if (! $response->successful()) {
                throw new \RuntimeException('Google Custom Search request failed: '.$response->status());
            }

            $response_items = Arr::get($response->json(), 'items', []);
            if (empty($response_items)) {
                break;
            }

            foreach ($response_items as $item) {
                $normalized = $this->normalizeItem($item);
                if (! empty($normalized)) {
                    $items[] = $normalized;
                }
            }

            $remaining -= $batch_size;
            $start += $batch_size;
        }

        return $items;
    }

    /**
     * Build search query from contact identity and optional location.
     *
     * @param \App\Contact $contact
     * @param array $options
     * @return string
     */
    protected function buildQuery(Contact $contact, array $options = [])
    {
        if (! empty($options['query'])) {
            return (string) $options['query'];
        }

        $name = trim((string) ($contact->supplier_business_name ?: $contact->name));
        $city = trim((string) ($contact->city ?? ''));
        $state = trim((string) ($contact->state ?? ''));
        $country = trim((string) ($contact->country ?? ''));

        if (empty($name)) {
            $name = 'business';
        }

        $query = '"'.$name.'" latest news';
        $location = trim(implode(' ', array_filter([$city, $state, $country])));
        if (! empty($location)) {
            $query .= ' "'.$location.'"';
        }

        return $query;
    }

    /**
     * Normalize raw provider item into common structure.
     *
     * @param array $item
     * @return array<string, mixed>|null
     */
    protected function normalizeItem(array $item)
    {
        $title = trim((string) Arr::get($item, 'title', ''));
        $url = trim((string) Arr::get($item, 'link', ''));

        if (empty($title) || empty($url)) {
            return null;
        }

        return [
            'provider' => 'google',
            'title' => $title,
            'snippet' => trim((string) Arr::get($item, 'snippet', '')),
            'canonical_url' => $url,
            'source_name' => (string) Arr::get($item, 'displayLink', ''),
            'published_at' => $this->extractPublishedAt($item),
            'raw_payload' => $item,
        ];
    }

    /**
     * Try extracting published date from known metadata keys.
     *
     * @param array $item
     * @return string|null
     */
    protected function extractPublishedAt(array $item)
    {
        $meta = Arr::get($item, 'pagemap.metatags.0', []);
        $candidates = [
            Arr::get($meta, 'article:published_time'),
            Arr::get($meta, 'og:published_time'),
            Arr::get($meta, 'article:modified_time'),
            Arr::get($meta, 'date'),
            Arr::get($meta, 'pubdate'),
        ];

        foreach ($candidates as $value) {
            if (empty($value)) {
                continue;
            }

            try {
                return Carbon::parse($value)->toDateTimeString();
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }
}
