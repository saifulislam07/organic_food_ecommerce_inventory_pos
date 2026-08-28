<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    /** A return puts stock back; every other type takes it away. */
    public const RETURNED = 'returned';

    protected static function booted(): void
    {
        // Undo whatever the adjustment did to the stock, on every delete path.
        static::deleting(function (self $adjustment) {
            $variant = $adjustment->productVariant;

            if (! $variant) {
                return;
            }

            $adjustment->type === self::RETURNED
                ? $variant->decrement('stock', $adjustment->quantity)
                : $variant->increment('stock', $adjustment->quantity);
        });
    }

    protected $fillable = [
        'product_variant_id', 'quantity', 'type', 'reason', 'adjustment_date',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
