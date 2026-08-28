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
        'name', 'name_en', 'name_bn', 'slug', 'image', 'description',
        'description_en', 'description_bn', 'is_active', 'sort_order',
    ];

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
