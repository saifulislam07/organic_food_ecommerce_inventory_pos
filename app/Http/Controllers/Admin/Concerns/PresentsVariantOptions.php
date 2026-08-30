<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Shared payload for the VariantSelect Vue component used by the purchase and
 * adjustment forms.
 */
trait PresentsVariantOptions
{
    protected function variantOptions(): Collection
    {
        return ProductVariant::with('product')
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (ProductVariant $variant) => $variant->product !== null)
            ->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'stock' => (int) $variant->stock,
                'cost_price' => $variant->cost_price === null ? null : (float) $variant->cost_price,
                // What the shop sells it for, so a form can offer that as the
                // default before anyone types a campaign price.
                'price' => (float) $variant->display_price,
            ])
            ->values();
    }
}
