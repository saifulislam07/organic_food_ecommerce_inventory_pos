<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    protected $fillable = ['combo_variant_id', 'component_variant_id', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function combo(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'combo_variant_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'component_variant_id');
    }
}
