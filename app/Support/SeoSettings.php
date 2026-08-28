<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Site-wide SEO defaults and analytics IDs. Individual pages override the title
 * and description through Blade sections; whatever they leave alone falls back
 * to these.
 */
class SeoSettings
{
    public const CACHE_KEY = 'seo_settings';

    public const FIELDS = [
        'seo_meta_title',
        'seo_meta_description',
        'seo_meta_keywords',
        'seo_og_image',
        'seo_google_analytics',
        'seo_google_site_verification',
        'seo_robots',
    ];

    /** @return array<string, string|null> */
    public static function all(): array
    {
        if (! self::tableIsReady()) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function () {
            $values = [];

            foreach (self::FIELDS as $key) {
                $values[$key] = Setting::get($key);
            }

            return $values;
        });
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function save(array $values): void
    {
        foreach (self::FIELDS as $key) {
            if (array_key_exists($key, $values)) {
                Setting::put($key, $values[$key]);
            }
        }

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Absolute URL of the default social sharing image, if one is uploaded. */
    public static function ogImageUrl(): ?string
    {
        $path = self::get('seo_og_image');

        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/'.$path);
    }

    /**
     * Search engines should be told to stay away until the shop chooses
     * otherwise, but the default here is to allow indexing.
     */
    public static function robots(): string
    {
        return self::get('seo_robots') ?: 'index, follow';
    }

    /** GA4 measurement ID, e.g. G-XXXXXXXXXX. */
    public static function analyticsId(): ?string
    {
        $id = self::get('seo_google_analytics');

        return blank($id) ? null : trim($id);
    }

    private static function tableIsReady(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
