<?php

namespace App\Models;

use App\Models\Concerns\CleansUpImages;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * One entry in a small, ordered list of storefront chrome — a navigation link,
 * a service promise, a promo tile, a payment chip, a footer link.
 *
 * These all used to be written into the Blade templates, so changing "Cash on
 * Delivery" to "bKash only" meant a deploy. They share one table rather than
 * five because the shape is the same in every case: a bilingual label, an
 * optional second line, an icon or picture, a link, an order and a switch.
 * GROUPS below says which of those fields each list actually shows, and that
 * one definition drives the admin form, the validator and the listing.
 */
class SiteBlock extends Model
{
    use CleansUpImages;

    /**
     * group => [label, hint, fields]
     *
     * `fields` names the inputs the admin form renders for that group. A field
     * left out here is not just hidden — it is never asked for, so a payment
     * chip cannot quietly carry a link that nothing renders.
     */
    public const GROUPS = [
        'header_menu' => [
            'label' => 'Header Menu',
            'hint' => 'উপরের সবুজ বারের মেনু লিংক।',
            'fields' => ['title', 'url', 'icon', 'highlight'],
        ],
        'service' => [
            'label' => 'Service Strip',
            'hint' => 'হোমপেজে ব্যানারের নিচের চারটি প্রতিশ্রুতি কার্ড।',
            'fields' => ['title', 'subtitle', 'icon'],
        ],
        'promo' => [
            'label' => 'Promo Tiles',
            'hint' => 'হোমপেজের প্রোডাক্ট সারির মাঝের বড় প্রোমো কার্ড।',
            'fields' => ['title', 'subtitle', 'url', 'image'],
        ],
        'payment' => [
            'label' => 'Payment Methods',
            'hint' => 'ফুটারের উপরের পেমেন্ট মাধ্যমের তালিকা।',
            'fields' => ['title', 'icon'],
        ],
        'footer_menu' => [
            'label' => 'Footer — My Account',
            'hint' => 'ফুটারের "My Account" কলামের লিংক।',
            'fields' => ['title', 'url'],
        ],
        'footer_bottom' => [
            'label' => 'Footer — Bottom Links',
            'hint' => 'ফুটারের একদম নিচের পলিসি লিংকের সারি।',
            'fields' => ['title', 'url'],
        ],
    ];

    /**
     * Every active row, grouped, read once per request.
     *
     * The header alone asks for four of these lists and the front page for two
     * more, on every page load. Mirrors how Setting caches its own table.
     */
    private static ?Collection $lists = null;

    protected static function booted(): void
    {
        static::deleting(fn (self $block) => self::deleteUploadedImage($block->image, 'blocks/'));
        static::saved(fn () => self::flush());
        static::deleted(fn () => self::flush());
    }

    /** The active rows of one list, in order. Empty when nothing is defined. */
    public static function list(string $group): Collection
    {
        self::$lists ??= self::query()->active()->sorted()->get()->groupBy('group');

        return self::$lists->get($group) ?? collect();
    }

    public static function flush(): void
    {
        self::$lists = null;
    }

    protected $fillable = [
        'group',
        'title_en', 'title_bn',
        'subtitle_en', 'subtitle_bn',
        'icon', 'image', 'url',
        'is_highlighted', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_highlighted' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** English is the fallback for a field the admin left blank in Bangla. */
    private function localised(string $field): ?string
    {
        $value = $this->{$field.'_'.app()->getLocale()} ?? null;

        return filled($value) ? $value : ($this->{$field.'_en'} ?: null);
    }

    public function getTitleAttribute(): ?string
    {
        return $this->localised('title');
    }

    public function getSubtitleAttribute(): ?string
    {
        return $this->localised('subtitle');
    }

    /**
     * Where the entry points.
     *
     * A bare slug is resolved against the site root, so an admin can type
     * "shop" or "/shop" and get the same place; a full URL is left alone.
     */
    public function getLinkAttribute(): string
    {
        $url = trim((string) $this->url);

        if ($url === '') {
            return url('/');
        }

        if (str_contains($url, '://') || str_starts_with($url, 'tel:') || str_starts_with($url, 'mailto:')) {
            return $url;
        }

        return url($url);
    }

    /** True for a link that leaves the site and so wants target="_blank". */
    public function getIsExternalAttribute(): bool
    {
        $url = trim((string) $this->url);

        return str_contains($url, '://') && ! str_starts_with($this->link, url('/'));
    }

    public function getImageUrlAttribute(): ?string
    {
        return filled($this->image) ? ImageStore::url($this->image) : null;
    }

    /** The Bootstrap icon name, with a per-group default so a card is never blank. */
    public function getIconNameAttribute(): string
    {
        if (filled($this->icon)) {
            return $this->icon;
        }

        return match ($this->group) {
            'service' => 'patch-check',
            'payment' => 'credit-card',
            default => 'chevron-right',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public static function groupLabel(string $group): string
    {
        return self::GROUPS[$group]['label'] ?? ucfirst(str_replace('_', ' ', $group));
    }

    /** Whether this group's form asks for the given field. */
    public static function groupHasField(string $group, string $field): bool
    {
        return in_array($field, self::GROUPS[$group]['fields'] ?? [], true);
    }
}
