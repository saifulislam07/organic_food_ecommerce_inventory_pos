<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'weight_kg', 'price', 'sale_price',
        'cost_price', 'stock', 'sku', 'is_active', 'sort_order', 'unit_id', 'unit_value',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** What this bundle is made of. Empty for an ordinary variant. */
    public function comboItems(): HasMany
    {
        return $this->hasMany(ComboItem::class, 'combo_variant_id');
    }

    /** Bundles this variant is a component of. */
    public function partOfCombos(): HasMany
    {
        return $this->hasMany(ComboItem::class, 'component_variant_id');
    }

    /**
     * Sellable quantity. A combo holds no stock of its own — it is limited by
     * whichever component runs out first.
     */
    public function getAvailableStockAttribute(): int
    {
        return app(InventoryService::class)->available($this);
    }

    public function isCombo(): bool
    {
        return $this->comboItems()->exists();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** "3 kg" when a unit is set, otherwise the free-text variant name. */
    public function getMeasureAttribute(): string
    {
        if (! $this->unit_id || $this->unit_value === null) {
            return $this->name;
        }

        return rtrim(rtrim(number_format((float) $this->unit_value, 3, '.', ''), '0'), '.')
            .' '.$this->unit->short_code;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getDisplayPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->price;
    }
}
