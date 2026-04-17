<?php

namespace Modules\Cms\Utils;

class BlogLocaleUtil
{
    public static function supported(): array
    {
        $configured = config('cms.blog_supported_locales', ['en', 'vi']);
        if (! is_array($configured) || empty($configured)) {
            return ['en', 'vi'];
        }

        return array_values(array_unique(array_map(static function ($locale) {
            return strtolower((string) $locale);
        }, $configured)));
    }

    public static function default(): string
    {
        $locale = 'vi';

        return in_array($locale, static::supported(), true) ? $locale : 'vi';
    }

    public static function normalize(?string $locale): string
    {
        $locale = strtolower((string) $locale);

        return in_array($locale, static::supported(), true) ? $locale : static::default();
    }

    public static function resolveRequestLocale(\Illuminate\Http\Request $request): string
    {
        $routeLocale = $request->route('locale');
        if (! empty($routeLocale) && in_array(strtolower((string) $routeLocale), static::supported(), true)) {
            return strtolower((string) $routeLocale);
        }

        $sessionLocale = (string) $request->session()->get('user.language');
        if (! empty($sessionLocale) && in_array(strtolower($sessionLocale), static::supported(), true)) {
            return strtolower($sessionLocale);
        }

        return static::normalize(app()->getLocale());
    }
}
