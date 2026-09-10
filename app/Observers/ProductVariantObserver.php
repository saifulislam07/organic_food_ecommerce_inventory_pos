<?php

namespace App\Observers;

use App\Models\ComboItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\PreorderStockArrived;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Tells the shop when a pre-order can finally be filled.
 *
 * Watches for stock crossing from nothing to something — a purchase received, a
 * returned item put back, a count corrected. Only the crossing, so restocking a
 * shelf that already had items on it says nothing.
 */
class ProductVariantObserver
{
    public function updated(ProductVariant $variant): void
    {
        if (! $variant->wasChanged('stock')) {
            return;
        }

        $before = (int) $variant->getOriginal('stock');
        $after = (int) $variant->stock;

        // The crossing, not the level: going 4 → 9 helps nobody who is waiting,
        // because nothing was ever out of stock.
        if ($before > 0 || $after <= 0) {
            return;
        }

        $this->quietly(fn () => $this->announce($variant), $variant);
    }

    private function announce(ProductVariant $variant): void
    {
        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        $inventory = app(InventoryService::class);

        foreach ($this->waitingOn($variant) as $item) {
            // A bundle only becomes sellable when every part of it is in, so
            // ask the inventory rather than assuming this delivery was enough.
            if (! $inventory->hasStockFor($item->variant, (int) $item->quantity)) {
                continue;
            }

            Notification::send($admins, new PreorderStockArrived($item->order, $item->variant));
        }
    }

    /**
     * The open pre-ordered lines this delivery could fill.
     *
     * Both the variant itself and any bundle built out of it: a combo carries
     * no stock of its own, so a component arriving is what unblocks it.
     *
     * @return \Illuminate\Support\Collection<int, OrderItem>
     */
    private function waitingOn(ProductVariant $variant)
    {
        $variantIds = ComboItem::where('component_variant_id', $variant->id)
            ->pluck('combo_variant_id')
            ->push($variant->id)
            ->unique()
            ->all();

        return OrderItem::with(['order', 'variant.comboItems.component', 'variant.product'])
            ->where('is_preorder', true)
            ->whereIn('product_variant_id', $variantIds)
            ->whereHas('order', fn ($query) => $query->whereNotIn('status', Order::CLOSED))
            ->get()
            ->filter(fn (OrderItem $item) => $item->order && $item->variant);
    }

    /**
     * A failed notification must never take the stock change down with it —
     * this runs inside the purchase and adjustment transactions.
     */
    private function quietly(callable $callback, ProductVariant $variant): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('Pre-order stock notification failed', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
