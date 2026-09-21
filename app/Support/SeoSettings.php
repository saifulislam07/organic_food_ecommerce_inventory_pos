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
        'seo_facebook_pixel',
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

    /**
     * The address this page wants to be known by.
     *
     * url()->current() drops the query string, which pointed every
     * ?category= listing at a bare /shop — telling Google to throw away the
     * very category URLs the sitemap asks it to index. Only the parameters
     * that genuinely change what is listed survive here, so tracking junk
     * (fbclid, utm_*) and thin ?search= results still collapse onto the
     * canonical listing instead of competing with it.
     */
    public const CANONICAL_PARAMS = ['category', 'page'];

    public static function canonicalUrl(): string
    {
        $params = array_filter(
            request()->only(self::CANONICAL_PARAMS),
            fn ($value) => filled($value)
        );

        // Page one is the listing itself, not a separate address.
        if (($params['page'] ?? null) == 1) {
            unset($params['page']);
        }

        ksort($params);

        return $params === []
            ? url()->current()
            : url()->current().'?'.http_build_query($params);
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

    /**
     * Meta Pixel ID — a plain number from Events Manager.
     *
     * Shop-wide default; a landing page may report to a different pixel of its
     * own, see LandingPage::pixelId().
     */
    public static function facebookPixelId(): ?string
    {
        $id = self::get('seo_facebook_pixel');

        return blank($id) ? null : trim($id);
    }
}
