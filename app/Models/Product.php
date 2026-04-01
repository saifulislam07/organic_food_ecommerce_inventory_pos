<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'name_en', 'name_bn', 'slug', 
        'short_description', 'short_description_en', 'short_description_bn',
        'description', 'description_en', 'description_bn',
        'image', 'gallery', 'is_active', 'is_featured', 'is_bestseller',
        'is_trending', 'is_preorder', 'meta_title', 'meta_description', 'sort_order'
    ];

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
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
        if (!$variant) return 0;
        return $variant->sale_price ?? $variant->price;
    }

    public function getHighestPriceAttribute()
    {
        $variant = $this->variants->last();
        if (!$variant) return 0;
        return $variant->sale_price ?? $variant->price;
    }

    public function getPriceRangeAttribute(): string
    {
        $low = $this->lowest_price;
        $high = $this->highest_price;
        if ($low == $high) return '৳' . number_format($low);
        return '৳' . number_format($low) . ' – ৳' . number_format($high);
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) return asset('assets/img/placeholder.png');
        if (str_starts_with($this->image, 'products/')) return asset('storage/' . $this->image);
        return asset('assets/img/products/' . $this->image);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->variants->contains(fn($v) => $v->sale_price !== null && $v->sale_price < $v->price);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->variants->contains(fn($v) => $v->stock > 0 && $v->is_active);
    }
}
