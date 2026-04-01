<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    protected $fillable = [
        'product_variant_id', 'quantity', 'type', 'reason', 'adjustment_date'
    ];

    protected $casts = [
        'adjustment_date' => 'date',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
