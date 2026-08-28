<?php

namespace App\Models;

use App\Models\Concerns\CleansUpImages;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use CleansUpImages;

    protected static function booted(): void
    {
        // Covers every delete path — the form, a bulk action, a cascade we
        // trigger ourselves, or tinker.
        static::deleting(fn (self $image) => self::deleteUploadedImage($image->path, 'products/'));
    }

    protected $fillable = ['product_id', 'path', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        if (filled($this->path) && ! str_contains($this->path, '/')) {
            return asset('assets/img/products/'.$this->path);
        }

        return ImageStore::url($this->path);
    }

    /** The thumbnail is whichever image the product points at. */
    public function getIsThumbnailAttribute(): bool
    {
        return $this->path === $this->product?->getRawOriginal('image');
    }
}
