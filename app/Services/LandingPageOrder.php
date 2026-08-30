<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\LandingPageItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Turns what a visitor picked on a landing page into an order.
 *
 * Nothing about money is read from the request. The form posts which item and
 * how many; every price, discount and delivery charge is looked up again here.
 * A landing page is a public URL handed to strangers on Facebook, and the one
 * thing a stranger must not be able to do is name their own price.
 */
class LandingPageOrder
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * What the visitor asked for, as item/quantity pairs.
     *
     * Quantities are clamped to the range the page allows; an item that is not
     * on this page, or was asked for zero times, is simply dropped.
     *
     * @return array<int, array{item: LandingPageItem, quantity: int}>
     */
    public function lines(LandingPage $page, array $input): array
    {
        $items = $page->items;

        if ($items->isEmpty()) {
            return [];
        }

        // A bundle is all-or-nothing: the visitor never chose anything, so the
        // page's own quantities are the order.
        if ($page->isBundle()) {
            return $items
                ->map(fn (LandingPageItem $item) => [
                    'item' => $item,
                    'quantity' => max(1, $item->min_qty),
                ])
                ->all();
        }

        if ($page->isMulti()) {
            $lines = [];

            foreach ((array) ($input['items'] ?? []) as $id => $row) {
                $quantity = (int) (is_array($row) ? ($row['qty'] ?? 0) : $row);

                if ($quantity < 1) {
                    continue;
                }

                $item = $items->firstWhere('id', (int) $id);

                if ($item) {
                    $lines[] = ['item' => $item, 'quantity' => $this->clamp($item, $quantity)];
                }
            }

            return $lines;
        }

        // Single: one package, chosen or defaulted.
        $item = $items->firstWhere('id', (int) ($input['item_id'] ?? 0))
            ?? $items->firstWhere('is_default', true)
            ?? $items->first();

        $quantity = (int) ($input['quantity'] ?? $item->min_qty);

        return [['item' => $item, 'quantity' => $this->clamp($item, $quantity)]];
    }

    /**
     * What those lines cost. The same method feeds the page and the order, so
     * the number quoted is the number charged.
     *
     * @param  array<int, array{item: LandingPageItem, quantity: int}>  $lines
     * @return array{subtotal: float, discount: float, delivery: float, total: float}
     */
    public function quote(LandingPage $page, array $lines, ?string $area): array
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += $line['item']->price() * $line['quantity'];
        }

        $subtotal = round($subtotal, 2);

        // A bundle price is a discount off the parts, recorded as one, so the
        // invoice still lists each item at a price the customer recognises.
        $discount = $page->isBundle()
            ? max(0.0, round($subtotal - $page->bundleTotal(), 2))
            : 0.0;

        $goods = round($subtotal - $discount, 2);
        $delivery = round($page->deliveryChargeFor($area, $goods), 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery' => $delivery,
            'total' => round($goods + $delivery, 2),
        ];
    }

    /**
     * Create the order and take the stock in one transaction.
     *
     * @param  array<int, array{item: LandingPageItem, quantity: int}>  $lines
     *
     * @throws RuntimeException when something on the page has since sold out
     */
    public function place(LandingPage $page, array $lines, array $customer, array $tracking): Order
    {
        if (empty($lines)) {
            throw new RuntimeException('কোনো প্রোডাক্ট নির্বাচন করা হয়নি।');
        }

        $quote = $this->quote($page, $lines, $customer['customer_area'] ?? null);

        return DB::transaction(function () use ($page, $lines, $customer, $tracking, $quote) {
            $order = Order::create([
                'customer_name' => $customer['customer_name'],
                'customer_phone' => $customer['customer_phone'],
                'customer_address' => $customer['customer_address'] ?? 'ঠিকানা ফোনে নেওয়া হবে',
                'customer_area' => $customer['customer_area'] ?? null,
                'notes' => $customer['notes'] ?? null,
                'subtotal' => $quote['subtotal'],
                'discount_amount' => $quote['discount'],
                'delivery_charge' => $quote['delivery'],
                'total' => $quote['total'],
                'payment_method' => 'cod',
                'source' => 'landing',
                'landing_page_id' => $page->id,
                'utm_source' => $tracking['utm_source'] ?? null,
                'utm_medium' => $tracking['utm_medium'] ?? null,
                'utm_campaign' => $tracking['utm_campaign'] ?? null,
                'utm_content' => $tracking['utm_content'] ?? null,
                'fbclid' => $tracking['fbclid'] ?? null,
            ]);

            foreach ($lines as $line) {
                /** @var LandingPageItem $item */
                $item = $line['item'];
                $variant = $item->variant;

                if (! $variant) {
                    throw new RuntimeException($item->label().' এখন আর পাওয়া যাচ্ছে না।');
                }

                // Combo-aware, row-locking, and it throws rather than
                // overselling — the same path a website checkout takes.
                $this->inventory->deduct($variant, $line['quantity']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->label(),
                    'variant_name' => $variant->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $item->price(),
                    'total' => round($item->price() * $line['quantity'], 2),
                ]);
            }

            return $order;
        });
    }

    /** Keep a posted quantity inside what the page offers. */
    private function clamp(LandingPageItem $item, int $quantity): int
    {
        $min = max(1, $item->min_qty);
        $max = max($min, $item->max_qty);

        return max($min, min($quantity, $max));
    }
}
