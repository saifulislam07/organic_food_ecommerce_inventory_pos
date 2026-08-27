<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['name', 'name_bn', 'short_code', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** "কেজি" in Bengali, "Kilogram" in English, falling back to whichever exists. */
    public function getLabelAttribute(): string
    {
        return app()->getLocale() === 'bn'
            ? ($this->name_bn ?: $this->name)
            : $this->name;
    }
}
