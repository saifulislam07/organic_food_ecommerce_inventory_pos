<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * The flat shape a "pick a product" widget consumes.
 *
 * The POS grid and the order editor are the same problem twice — search the
 * catalogue, show a price and what is left in stock, add a line — so they read
 * the same shape. Accessors like image_url are not serialised by default, so
 * they are spelled out here rather than hoped for.
 */
trait PresentsSellableVariants
{
    protected function presentVariant(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'name' => $variant->name,
            'sku' => $variant->sku,
            'price' => (float) ($variant->sale_price ?? $variant->price),
            // The list price too, so a tile can strike it through when the
            // variant is on offer instead of silently selling at the lower one.
            'list_price' => (float) $variant->price,
            'on_sale' => $variant->is_on_sale,
            'stock' => $variant->available_stock,
            'product_name' => $variant->product->name,
            'category_id' => $variant->product->category_id,
            'category' => $variant->product->category?->name,
            'image' => $variant->product->image_url,
        ];
    }

    /**
     * Variants matching what someone is typing, by product name or SKU.
     *
     * @return Collection<int, array>
     */
    protected function searchVariants(string $query, int $limit = 20)
    {
        return ProductVariant::with('product.category', 'comboItems.component')
            ->where(function ($builder) use ($query) {
                $builder
                    ->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get()
            ->filter(fn (ProductVariant $variant) => $variant->product !== null)
            ->map(fn (ProductVariant $variant) => $this->presentVariant($variant))
            ->values();
    }
}
