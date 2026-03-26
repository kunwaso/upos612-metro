<?php

namespace App\Utils;

use App\Contact;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class GoogleContactFeedProvider implements ContactFeedProviderInterface
{
    /**
     * @var \App\Utils\SerpApiGoogleContactFeedProvider|null
     */
    protected $serpApiProvider;

    public function __construct(?SerpApiGoogleContactFeedProvider $serpApiProvider = null)
    {
        $this->serpApiProvider = $serpApiProvider;
    }

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
        $limit = isset($options['limit']) ? (int) $options['limit'] : (int) config('services.google_custom_search.default_limit', 30);
        $limit = max(1, min($limit, 30));
        $query = $this->buildQuery($contact, $options);
        $options['query'] = $query;

        try {
            return $this->searchGoogleCustomSearch($contact, $options, $limit, $query);
        } catch (\Throwable $e) {
            if (! $this->shouldUseSerpApiFallback($e)) {
                throw $e;
            }

            try {
                return $this->serpApiProvider->search($contact, $options);
            } catch (\Throwable $fallbackException) {
                throw new \RuntimeException($e->getMessage().' | SerpApi fallback failed: '.$fallbackException->getMessage());
            }
        }
    }

    /**
     * @param \App\Contact $contact
     * @param array $options
     * @param int $limit
     * @param string $query
     * @return array<int, array<string, mixed>>
     */
    protected function searchGoogleCustomSearch(Contact $contact, array $options, $limit, $query)
    {
        $api_key = (string) config('services.google_custom_search.api_key');
        $search_engine_id = (string) config('services.google_custom_search.search_engine_id');
        $base_url = (string) config('services.google_custom_search.base_url', 'https://www.googleapis.com/customsearch/v1');
        $timeout = (int) config('services.google_custom_search.timeout', 10);
        $verify_ssl = config('services.google_custom_search.verify_ssl', true);
        $ca_bundle = trim((string) config('services.google_custom_search.ca_bundle', ''));

        if (empty($api_key) || empty($search_engine_id)) {
            throw new \RuntimeException('Google Custom Search credentials are not configured.');
        }

        $date_restrict = $this->buildDateRestrict($options['published_after'] ?? null);
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
            if (! empty($date_restrict)) {
                $params['dateRestrict'] = $date_restrict;
            }

            $request = Http::timeout($timeout)->acceptJson();
            if (! $this->isSslVerificationEnabled($verify_ssl)) {
                $request = $request->withoutVerifying();
            } elseif (! empty($ca_bundle)) {
                $request = $request->withOptions(['verify' => $ca_bundle]);
            }

            try {
                $response = $request->get($base_url, $params);
            } catch (\Throwable $e) {
                if ($this->isSslCertificateError($e->getMessage())) {
                    throw new \RuntimeException(
                        'Google SSL verification failed (cURL error 60). Set GOOGLE_CUSTOM_SEARCH_CA_BUNDLE to a valid cacert.pem path, or set GOOGLE_CUSTOM_SEARCH_VERIFY_SSL=false for local development only.'
                    );
                }

                throw $e;
            }

            if (! $response->successful()) {
                throw new \RuntimeException($this->buildGoogleRequestErrorMessage($response));
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
     * @param \Throwable $exception
     * @return bool
     */
    protected function shouldUseSerpApiFallback(\Throwable $exception)
    {
        if (! $this->serpApiProvider) {
            return false;
        }

        $fallback_enabled = config('services.serpapi_google.fallback_enabled', true);
        $fallback_provider = (string) config('services.contact_feeds.google_fallback_provider', 'serpapi_google');
        $has_fallback_key = trim((string) config('services.serpapi_google.api_key', '')) !== '';
        $message = strtolower(trim((string) $exception->getMessage()));

        if (! $this->isSslVerificationEnabled($fallback_enabled) || ! $has_fallback_key || $fallback_provider !== 'serpapi_google') {
            return false;
        }

        return $message === ''
            || strpos($message, 'google custom search credentials are not configured') !== false
            || strpos($message, 'google custom search request failed: 403') !== false
            || strpos($message, 'google custom search request failed: 429') !== false
            || strpos($message, 'permission_denied') !== false
            || strpos($message, 'forbidden') !== false
            || strpos($message, 'quota') !== false
            || strpos($message, 'custom search json api') !== false
            || strpos($message, 'api key not valid') !== false;
    }

    /**
     * Normalize SSL verification config flag.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isSslVerificationEnabled($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized === null ? true : $normalized;
    }

    /**
     * Detect certificate-chain verification failures.
     *
     * @param string $message
     * @return bool
     */
    protected function isSslCertificateError($message)
    {
        $message = (string) $message;

        return stripos($message, 'cURL error 60') !== false
            || stripos($message, 'SSL certificate problem') !== false;
    }

    /**
     * Build request error message from Google API payload.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return string
     */
    protected function buildGoogleRequestErrorMessage(Response $response)
    {
        $status = (int) $response->status();
        $message = trim((string) Arr::get($response->json(), 'error.message', ''));
        $reason = trim((string) Arr::get($response->json(), 'error.errors.0.reason', ''));
        $parts = ['Google Custom Search request failed: '.$status];

        if (! empty($message)) {
            $parts[] = $message;
        }

        if (! empty($reason) && stripos($message, $reason) === false) {
            $parts[] = 'Reason: '.$reason;
        }

        return implode(' | ', $parts);
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
        $name = trim((string) ($contact->supplier_business_name ?: $contact->name));
        $name = str_replace('"', '', $name);
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
            'image_url' => $this->extractImageUrl($item),
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

    /**
     * Approximate Google date restriction for fresher incremental pulls.
     *
     * @param string|null $published_after
     * @return string|null
     */
    protected function buildDateRestrict($published_after)
    {
        if (empty($published_after)) {
            return null;
        }

        try {
            $days = max(1, Carbon::parse($published_after)->diffInDays(now()) + 1);
        } catch (\Exception $e) {
            return null;
        }

        if ($days <= 30) {
            return 'd'.$days;
        }

        if ($days <= 180) {
            return 'w'.(int) ceil($days / 7);
        }

        if ($days <= 730) {
            return 'm'.(int) ceil($days / 30);
        }

        return 'y'.(int) ceil($days / 365);
    }

    /**
     * Extract a preview image URL from common Google result metadata fields.
     *
     * @param array $item
     * @return string|null
     */
    protected function extractImageUrl(array $item)
    {
        $meta = Arr::get($item, 'pagemap.metatags.0', []);
        $candidates = [
            Arr::get($item, 'pagemap.cse_image.0.src'),
            Arr::get($item, 'pagemap.cse_thumbnail.0.src'),
            Arr::get($item, 'pagemap.imageobject.0.url'),
            Arr::get($item, 'pagemap.imageobject.0.contenturl'),
            Arr::get($meta, 'og:image'),
            Arr::get($meta, 'og:image:url'),
            Arr::get($meta, 'twitter:image'),
            Arr::get($meta, 'image'),
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeImageUrl($candidate);
            if (! empty($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * Normalize preview image URL.
     *
     * @param mixed $url
     * @return string|null
     */
    protected function normalizeImageUrl($url)
    {
        $url = trim((string) $url);
        if (empty($url)) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }
}
