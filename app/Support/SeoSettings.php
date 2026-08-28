<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Site-wide SEO defaults and analytics IDs. Individual pages override the title
 * and description through Blade sections; whatever they leave alone falls back
 * to these.
 */
class SeoSettings
{
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
        $values = [];

        foreach (self::FIELDS as $field) {
            $values[$field] = Setting::get($field);
        }

        return $values;
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
        Setting::flush();
    }

    /** Absolute URL of the default social sharing image, if one is uploaded. */
    public static function ogImageUrl(): ?string
    {
        $path = self::get('seo_og_image');

        return blank($path) ? null : ImageStore::url($path);
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
}
