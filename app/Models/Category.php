<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name', 'name_en', 'name_bn', 'slug', 'image', 'description', 
        'description_en', 'description_bn', 'is_active', 'sort_order'
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
        if (!$this->image) return asset('assets/img/placeholder.png');
        if (str_starts_with($this->image, 'categories/')) return asset('storage/' . $this->image);
        return asset('assets/img/categories/' . $this->image);
    }
}
