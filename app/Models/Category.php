<?php

namespace App\Models;

use App\Models\Concerns\CleansUpImages;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use CleansUpImages;

    protected static function booted(): void
    {
        static::deleting(fn (self $category) => self::deleteUploadedImage(
            $category->getRawOriginal('image'), 'categories/'
        ));
    }

    protected $fillable = [
        'name', 'name_en', 'name_bn', 'slug', 'theme', 'landing_defaults',
        'image', 'description', 'description_en', 'description_bn',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'landing_defaults' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * The draft every promotion in this category opens with.
     *
     * Read once, when a campaign page is created — never at render time. An
     * admin rewriting a category's selling points must not silently rewrite the
     * copy of a campaign that has been running for a week.
     *
     * @return array{sections: array, features: array, faqs: array, specs: array, cta_text: ?string}
     */
    public function landingDefaults(): array
    {
        $defaults = is_array($this->landing_defaults) ? $this->landing_defaults : [];

        return [
            'sections' => array_values(array_intersect(
                is_array($defaults['sections'] ?? null) ? $defaults['sections'] : [],
                array_keys(LandingPage::BLOCKS)
            )),
            'features' => array_values(array_filter(
                is_array($defaults['features'] ?? null) ? $defaults['features'] : [],
                fn ($line) => filled($line)
            )),
            'faqs' => array_values(array_filter(
                is_array($defaults['faqs'] ?? null) ? $defaults['faqs'] : [],
                fn ($row) => is_array($row) && filled($row['q'] ?? null)
            )),
            'specs' => array_values(array_filter(
                is_array($defaults['specs'] ?? null) ? $defaults['specs'] : [],
                fn ($row) => is_array($row) && filled($row['label'] ?? null)
            )),
            'cta_text' => filled($defaults['cta_text'] ?? null) ? $defaults['cta_text'] : null,
        ];
    }

    /** Whether there is anything here worth offering to prefill a page with. */
    public function hasLandingDefaults(): bool
    {
        $defaults = $this->landingDefaults();

        return (bool) ($defaults['sections'] || $defaults['features']
            || $defaults['faqs'] || $defaults['specs'] || $defaults['cta_text']);
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_{$locale}"} ?? $this->attributes['name'];
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"description_{$locale}"} ?? $this->attributes['description'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getImageUrlAttribute(): string
    {
        // Legacy bare filenames have no shipped directory behind them any more.
        return ImageStore::url(
            str_contains((string) $this->image, '/') ? $this->image : null
        );
    }
}
