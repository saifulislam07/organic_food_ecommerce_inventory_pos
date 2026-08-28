<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'path', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }

        return str_starts_with($this->path, 'products/')
            ? asset('storage/'.$this->path)
            : asset('assets/img/products/'.$this->path);
    }

    /** The thumbnail is whichever image the product points at. */
    public function getIsThumbnailAttribute(): bool
    {
        return $this->path === $this->product?->getRawOriginal('image');
    }
}
