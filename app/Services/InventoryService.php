<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The one place that knows how stock moves.
 *
 * A combo has no stock of its own — what it can sell is limited by whichever of
 * its components runs out first, and selling one draws down every component.
 */
class InventoryService
{
    /** How many of this variant can still be sold. */
    public function available(ProductVariant $variant): int
    {
        $variant->loadMissing('comboItems.component');

        if ($variant->comboItems->isEmpty()) {
            return (int) $variant->stock;
        }

        return (int) $variant->comboItems
            ->map(function ($item) {
                if ($item->quantity < 1 || ! $item->component) {
                    return 0;
                }

                return intdiv((int) $item->component->stock, $item->quantity);
            })
            ->min();
    }

    public function hasStockFor(ProductVariant $variant, int $quantity): bool
    {
        return $this->available($variant) >= $quantity;
    }

    /**
     * Take stock for a sale. Call inside a transaction — a combo touches several
     * rows and a partial deduction would leave the books wrong.
     *
     * @throws RuntimeException when there is not enough to cover the sale
     */
    public function deduct(ProductVariant $variant, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $variant->loadMissing('comboItems.component', 'product');

        if ($variant->comboItems->isEmpty()) {
            $this->deductSimple($variant, $quantity);

            return;
        }

        foreach ($variant->comboItems as $item) {
            $needed = $item->quantity * $quantity;

            if (! $item->component) {
                throw new RuntimeException("A component of {$this->label($variant)} no longer exists.");
            }

            $this->deductSimple($item->component, $needed);
        }
    }

    /** Put stock back, e.g. when an order is cancelled. */
    public function restore(ProductVariant $variant, int $quantity): void
    {
        if ($quantity < 1) {
            return;
        }

        $variant->loadMissing('comboItems.component');

        if ($variant->comboItems->isEmpty()) {
            $variant->increment('stock', $quantity);

            return;
        }

        foreach ($variant->comboItems as $item) {
            $item->component?->increment('stock', $item->quantity * $quantity);
        }
    }

    private function deductSimple(ProductVariant $variant, int $quantity): void
    {
        // Lock the row so two simultaneous checkouts cannot both pass the check.
        $locked = ProductVariant::whereKey($variant->getKey())->lockForUpdate()->first();

        if (! $locked || $locked->stock < $quantity) {
            throw new RuntimeException(
                'Not enough stock for '.$this->label($locked ?? $variant).
                ' — '.(int) ($locked->stock ?? 0).' left, '.$quantity.' needed.'
            );
        }

        $locked->decrement('stock', $quantity);
        $variant->stock = $locked->stock - $quantity;
    }

    private function label(ProductVariant $variant): string
    {
        $variant->loadMissing('product');

        return trim(($variant->product->name ?? 'Product').' ('.$variant->name.')');
    }

    /** Convenience for callers that already have a transaction to open. */
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
