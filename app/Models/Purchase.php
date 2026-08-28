<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected static function booted(): void
    {
        // A purchase put the stock in, so removing it has to take that back --
        // whichever route the delete came through, single or bulk.
        static::deleting(function (self $purchase) {
            $purchase->productVariant?->decrement('stock', $purchase->quantity);
        });
    }

    protected $fillable = [
        'supplier_id', 'product_variant_id', 'purchase_price',
        'quantity', 'purchase_date', 'notes', 'paid_from',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
