<?php

namespace Modules\ProjectX\Utils;

use Illuminate\Support\Facades\DB;

class SiteManagerUtil
{
    public const WELCOME_KEYS = [
        'site_name',
        'hero_title',
        'hero_subtitle',
        'cta_label',
        'cta_url',
        'nav_items',
        'footer_copyright',
        'logo_url',
    ];

    /**
     * Get key-value settings for the given business (or global when business_id is null).
     *
     * @param  int|null  $business_id
     * @param  array<string>  $keys
     * @return array<string, mixed>
     */
    public function getSettings(?int $business_id, array $keys): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        $query = DB::table('projectx_site_settings')
            ->where(function ($q) use ($business_id) {
                $q->whereNull('business_id');
                if ($business_id !== null) {
                    $q->orWhere('business_id', $business_id);
                }
            })
            ->whereIn('key', $keys)
            ->orderByRaw('business_id IS NULL ASC'); // business-specific rows after global

        $rows = $query->get();
        $out = [];
        foreach ($rows as $row) {
            $val = $row->value;
            if (in_array($row->key, ['nav_items'], true)) {
                $val = json_decode($row->value, true) ?: [];
            }
            $out[$row->key] = $val;
        }

        return $out;
    }

    /**
     * Set multiple settings. Existing keys are updated; new keys are inserted.
     *
     * @param  int|null  $business_id
     * @param  array<string, mixed>  $key_value_array
     */
    public function setSettings(?int $business_id, array $key_value_array): void
    {
        if (! $this->tableExists()) {
            return;
        }

        foreach ($key_value_array as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            DB::table('projectx_site_settings')->updateOrInsert(
                [
                    'business_id' => $business_id,
                    'key' => $key,
                ],
                [
                    'value' => $value,
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Data for the welcome (landing) view. Uses first business when business_id is null (e.g. public page).
     *
     * @param  int|null  $business_id
     * @return array<string, mixed>
     */
    public function getWelcomeViewData(?int $business_id = null): array
    {
        if ($business_id === null && $this->tableExists()) {
            $first = DB::table('projectx_site_settings')
                ->whereNotNull('business_id')
                ->limit(1)
                ->value('id');
            if ($first === null) {
                $firstBusiness = DB::table('business')->orderBy('id')->value('id');
                $business_id = $firstBusiness ? (int) $firstBusiness : null;
            }
        }

        $keys = self::WELCOME_KEYS;
        $settings = $this->getSettings($business_id, $keys);
        $nav_items = $this->normalizeNavItems($settings['nav_items'] ?? []);
        $logo_url = $this->normalizeLogoUrl($settings['logo_url'] ?? null);

        return [
            'siteName' => $settings['site_name'] ?? config('app.name'),
            'heroTitle' => $settings['hero_title'] ?? config('app.name'),
            'heroSubtitle' => $settings['hero_subtitle'] ?? '',
            'ctaLabel' => $settings['cta_label'] ?? 'Sign In',
            'ctaUrl' => $settings['cta_url'] ?? route('login'),
            'navItems' => $nav_items,
            'footerCopyright' => $settings['footer_copyright'] ?? '© ' . date('Y') . ' ' . config('app.name'),
            'logoUrl' => $logo_url,
        ];
    }

    protected function tableExists(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('projectx_site_settings');
    }

    /**
     * @param  mixed  $items
     * @return array<int, array{label: string, url: string}>
     */
    protected function normalizeNavItems($items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            $label = '';
            $url = '';

            if (is_array($item)) {
                $label = trim((string) ($item['label'] ?? $item['text'] ?? ''));
                $url = trim((string) ($item['url'] ?? $item['href'] ?? ''));
            } elseif (is_string($item)) {
                $label = trim($item);
            }

            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'url' => $this->normalizeNavUrl($url),
            ];
        }

        return $normalized;
    }

    protected function normalizeNavUrl(string $url): string
    {
        if ($url === '') {
            return '#';
        }

        $lower_url = strtolower($url);
        if (
            strpos($lower_url, 'http://') === 0
            || strpos($lower_url, 'https://') === 0
            || strpos($lower_url, 'mailto:') === 0
            || strpos($lower_url, 'tel:') === 0
            || strpos($url, '#') === 0
            || strpos($url, '/') === 0
        ) {
            return $url;
        }

        return '/' . ltrim($url, '/');
    }

    /**
     * @param  mixed  $logo_url
     */
    protected function normalizeLogoUrl($logo_url): string
    {
        $logo = trim((string) $logo_url);
        if ($logo === '') {
            return asset('modules/projectx/media/logos/landing.svg');
        }

        $lower_logo = strtolower($logo);
        if (
            strpos($lower_logo, 'http://') === 0
            || strpos($lower_logo, 'https://') === 0
            || strpos($lower_logo, 'data:image/') === 0
            || strpos($logo, '/') === 0
        ) {
            return $logo;
        }

        return asset(ltrim($logo, '/'));
    }
}
