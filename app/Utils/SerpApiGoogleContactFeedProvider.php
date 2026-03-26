<?php

namespace App\Utils;

use App\Contact;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SerpApiGoogleContactFeedProvider implements ContactFeedProviderInterface
{
    /**
     * Search Google news results through SerpApi and return normalized items.
     *
     * @param \App\Contact $contact
     * @param array $options
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException
     */
    public function search(Contact $contact, array $options = [])
    {
        $api_key = (string) config('services.serpapi_google.api_key');
        $base_url = (string) config('services.serpapi_google.base_url', 'https://serpapi.com/search');
        $timeout = (int) config('services.serpapi_google.timeout', 10);
        $verify_ssl = config('services.serpapi_google.verify_ssl', true);
        $ca_bundle = trim((string) config('services.serpapi_google.ca_bundle', ''));

        if (empty($api_key)) {
            throw new \RuntimeException('SerpApi fallback key is not configured.');
        }

        $limit = isset($options['limit']) ? (int) $options['limit'] : 30;
        $limit = max(1, min($limit, 30));
        $query = trim((string) ($options['query'] ?? $this->buildQuery($contact)));
        $location = $this->buildLocation($contact);
        $items = [];
        $remaining = $limit;
        $start = 0;

        while ($remaining > 0 && $start <= 90) {
            $batch_size = min(10, $remaining);
            $params = [
                'engine' => 'google',
                'tbm' => 'nws',
                'q' => $query,
                'api_key' => $api_key,
                'num' => $batch_size,
                'start' => $start,
                'no_cache' => 'true',
                'output' => 'json',
            ];

            if (! empty($location)) {
                $params['location'] = $location;
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
                        'SerpApi SSL verification failed (cURL error 60). Set SERPAPI_CA_BUNDLE to a valid cacert.pem path, or set SERPAPI_VERIFY_SSL=false for local development only.'
                    );
                }

                throw $e;
            }

            if (! $response->successful()) {
                throw new \RuntimeException($this->buildSerpApiRequestErrorMessage($response));
            }

            $payload = $response->json();
            $response_items = Arr::get($payload, 'news_results', []);
            if (empty($response_items)) {
                $response_items = Arr::get($payload, 'organic_results', []);
            }

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
     * @param \Illuminate\Http\Client\Response $response
     * @return string
     */
    protected function buildSerpApiRequestErrorMessage(Response $response)
    {
        $status = (int) $response->status();
        $payload = $response->json();
        $message = trim((string) (Arr::get($payload, 'error') ?: Arr::get($payload, 'search_metadata.status')));
        $parts = ['SerpApi Google News request failed: '.$status];

        if (! empty($message)) {
            $parts[] = $message;
        }

        return implode(' | ', $parts);
    }

    /**
     * @param \App\Contact $contact
     * @return string
     */
    protected function buildQuery(Contact $contact)
    {
        $name = trim((string) ($contact->supplier_business_name ?: $contact->name));
        $name = str_replace('"', '', $name);

        if (empty($name)) {
            $name = 'business';
        }

        return '"'.$name.'" latest news';
    }

    /**
     * @param \App\Contact $contact
     * @return string|null
     */
    protected function buildLocation(Contact $contact)
    {
        $city = trim((string) ($contact->city ?? ''));
        $state = trim((string) ($contact->state ?? ''));
        $country = trim((string) ($contact->country ?? ''));
        $location = trim(implode(', ', array_filter([$city, $state, $country])));

        return $location !== '' ? $location : null;
    }

    /**
     * @param array $item
     * @return array<string, mixed>|null
     */
    protected function normalizeItem(array $item)
    {
        $title = trim((string) Arr::get($item, 'title', ''));
        $url = trim((string) (Arr::get($item, 'link', '') ?: Arr::get($item, 'news_link', '')));

        if (empty($title) || empty($url)) {
            return null;
        }

        $raw_payload = $item;
        $raw_payload['_feed_source'] = 'serpapi_google';

        return [
            'provider' => 'google',
            'title' => $title,
            'snippet' => trim((string) (Arr::get($item, 'snippet', '') ?: Arr::get($item, 'snippet_highlighted_words.0', ''))),
            'image_url' => $this->extractImageUrl($item),
            'canonical_url' => $url,
            'source_name' => trim((string) (Arr::get($item, 'source.name', '') ?: Arr::get($item, 'source', '') ?: Arr::get($item, 'displayed_link', ''))),
            'published_at' => $this->extractPublishedAt($item),
            'raw_payload' => $raw_payload,
        ];
    }

    /**
     * @param array $item
     * @return string|null
     */
    protected function extractPublishedAt(array $item)
    {
        $candidates = [
            Arr::get($item, 'iso_date'),
            Arr::get($item, 'date_utc'),
            Arr::get($item, 'date'),
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
     * @param array $item
     * @return string|null
     */
    protected function extractImageUrl(array $item)
    {
        $candidates = [
            Arr::get($item, 'thumbnail'),
            Arr::get($item, 'thumbnail_small'),
            Arr::get($item, 'inline_images.0.thumbnail'),
            Arr::get($item, 'rich_snippet.top.thumbnail'),
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
