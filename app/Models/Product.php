<?php

namespace App\Models;

use App\Models\Concerns\CleansUpImages;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use CleansUpImages;

    protected static function booted(): void
    {
        static::deleting(function (self $product) {
            // Deleted one by one so ProductImage's own hook removes each file;
            // the database cascade would take the rows but leave the images.
            $product->images->each->delete();

            self::deleteUploadedImage($product->getRawOriginal('image'), 'products/');
        });
    }

    protected $fillable = [
        'category_id', 'name', 'name_en', 'name_bn', 'slug',
        'short_description', 'short_description_en', 'short_description_bn',
        'description', 'description_en', 'description_bn',
        'image', 'gallery', 'is_active', 'is_featured', 'is_bestseller',
        'is_trending', 'is_preorder', 'is_combo', 'meta_title', 'meta_description', 'sort_order',
    ];

    /**
     * SQL for the name this product renders under right now, mirroring
     * getNameAttribute() so sorting matches what the shopper sees.
     */
    public static function displayNameExpression(): Expression
    {
        $locale = in_array(app()->getLocale(), ['en', 'bn'], true) ? app()->getLocale() : 'en';

        return DB::raw("COALESCE(products.name_{$locale}, products.name)");
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{"name_{$locale}"} ?? $this->attributes['name'];
    }

    public function getShortDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"short_description_{$locale}"} ?? $this->attributes['short_description'];
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        return $this->{"description_{$locale}"} ?? $this->attributes['description'];
    }

    protected $casts = [
        'gallery' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_trending' => 'boolean',
        'is_preorder' => 'boolean',
        'is_combo' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Up to MAX_IMAGES gallery photos, ordered as the admin arranged them. */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /** How many photos one product may carry. */
    public const MAX_IMAGES = 5;

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Everything a product card reads, in three queries instead of one per
     * variant: is_in_stock walks the variants, and a combo variant's stock is
     * worked out from its components.
     */
    public function scopeWithCardData($query)
    {
        return $query->with(['category', 'variants.comboItems.component']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBestseller($query)
    {
        return $query->where('is_bestseller', true);
    }

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    public function getLowestPriceAttribute()
    {
        $variant = $this->variants->first();
        if (! $variant) {
            return 0;
        }

        return $variant->sale_price ?? $variant->price;
    }

    public function getHighestPriceAttribute()
    {
        $variant = $this->variants->last();
        if (! $variant) {
            return 0;
        }

        return $variant->sale_price ?? $variant->price;
    }

    public function getPriceRangeAttribute(): string
    {
        $low = $this->lowest_price;
        $high = $this->highest_price;
        if ($low == $high) {
            return '৳'.number_format($low);
        }

        return '৳'.number_format($low).' – ৳'.number_format($high);
    }

    public function getImageUrlAttribute(): string
    {
        // A bare filename is a shipped asset from the seed data.
        if (filled($this->image) && ! str_contains($this->image, '/')) {
            return asset('assets/img/products/'.$this->image);
        }

        return ImageStore::url($this->image);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->variants->contains(fn ($v) => $v->sale_price !== null && $v->sale_price < $v->price);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->variants->contains(fn ($v) => $v->is_active && $v->available_stock > 0);
    }
}
