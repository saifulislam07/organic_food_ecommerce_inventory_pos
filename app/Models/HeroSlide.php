<?php

namespace App\Models;

use App\Models\Concerns\CleansUpImages;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;

/**
 * One panel of the front page hero carousel.
 *
 * Before this table existed the hero was a single fixed panel built from the
 * hero_title / hero_desc settings and a shipped picture. That panel is still
 * what a shop with no slides gets — see fallback() — so an empty table never
 * means an empty front page.
 */
class HeroSlide extends Model
{
    use CleansUpImages;

    /** The picture a slide with no upload of its own shows. */
    public const DEFAULT_IMAGE = 'images/hero-mango.png';

    protected static function booted(): void
    {
        static::deleting(fn (self $slide) => self::deleteUploadedImage($slide->image, 'sliders/'));
    }

    protected $fillable = [
        'badge_en', 'badge_bn',
        'title_en', 'title_bn',
        'subtitle_en', 'subtitle_bn',
        'image',
        'button_text_en', 'button_text_bn', 'button_url',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The panel to show when nobody has added a slide yet.
     *
     * Unsaved on purpose: it is a view of the settings, so editing them keeps
     * working exactly as it did, and the row count stays an honest zero.
     */
    public static function fallback(): self
    {
        return new self([
            'badge_en' => '100% Pure & Organic',
            'badge_bn' => '১০০% খাঁটি ও অর্গানিক',
            'title_en' => Setting::value('hero_title', 'en')
                ?: 'Pure & Organic <br><span>Nature</span> Online Market',
            'title_bn' => Setting::value('hero_title', 'bn'),
            'subtitle_en' => Setting::value('hero_desc', 'en')
                ?: 'Directly from Chapainawabganj to your doorstep.',
            'subtitle_bn' => Setting::value('hero_desc', 'bn'),
        ]);
    }

    /** English is the fallback for a field the admin left blank in Bangla. */
    private function localised(string $field): ?string
    {
        $value = $this->{$field.'_'.app()->getLocale()} ?? null;

        return filled($value) ? $value : ($this->{$field.'_en'} ?: null);
    }

    public function getBadgeAttribute(): ?string
    {
        return $this->localised('badge');
    }

    public function getTitleAttribute(): ?string
    {
        return $this->localised('title');
    }

    public function getSubtitleAttribute(): ?string
    {
        return $this->localised('subtitle');
    }

    public function getButtonTextAttribute(): string
    {
        return $this->localised('button_text')
            ?: (app()->getLocale() === 'bn' ? 'শপ করুন' : 'Shop Now');
    }

    /** A slide with no link of its own points at the shop. */
    public function getButtonLinkAttribute(): string
    {
        return filled($this->button_url) ? $this->button_url : route('shop');
    }

    public function getImageUrlAttribute(): string
    {
        return ImageStore::url($this->image, self::DEFAULT_IMAGE);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
