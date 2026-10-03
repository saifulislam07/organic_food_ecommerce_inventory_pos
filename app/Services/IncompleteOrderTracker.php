<?php

namespace App\Services;

use App\Models\IncompleteOrder;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Support\Bangla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Keeps a copy of a checkout while it is being filled in, so a customer who
 * gives up halfway can still be called.
 *
 * Nothing here trusts the browser about money: the lines and prices are read
 * from the cart or the landing page on the server, the same way an order is.
 */
class IncompleteOrderTracker
{
    private const SESSION_KEY = 'incomplete_order_visitor';

    /** A lead with the same number this recent is treated as the same person. */
    private const SAME_PERSON_DAYS = 7;

    public function __construct(private InventoryService $inventory) {}

    /**
     * Digits only, without the 88 country prefix, or null when it is not a
     * Bangladeshi mobile number yet — half-typed numbers are not worth a row.
     */
    public static function normalisePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', Bangla::latinDigits((string) $phone));

        if (! preg_match('/^(?:88)?(01[3-9]\d{8})$/', $digits, $match)) {
            return null;
        }

        return $match[1];
    }

    /** Survives login (the session is regenerated, not emptied). */
    public function visitorKey(): string
    {
        $key = session(self::SESSION_KEY);

        if (! $key) {
            $key = Str::random(40);
            session([self::SESSION_KEY => $key]);
        }

        return $key;
    }

    public function captureCart(Request $request, CartService $cart): ?IncompleteOrder
    {
        $phone = self::normalisePhone($request->input('customer_phone'));

        if (! $phone || $cart->isEmpty()) {
            return null;
        }

        $pickup = $request->input('delivery_type') === 'pickup';
        $area = $request->input('customer_area') === 'dhaka_outside' ? 'dhaka_outside' : 'dhaka_inside';
        $subtotal = $cart->getSubtotal();
        $discount = $cart->getDiscount();
        $delivery = $pickup ? 0.0 : $cart->getDeliveryCharge($area);

        $items = array_map(fn (array $item) => [
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'],
            'product_name' => $item['product_name'],
            'variant_name' => $item['variant_name'] ?? null,
            'quantity' => (int) $item['quantity'],
            'price' => (float) $item['price'],
            'subtotal' => (float) $item['subtotal'],
            'is_preorder' => (bool) ($item['is_preorder'] ?? false),
        ], array_values($cart->getItems()));

        return $this->store($request, 'website', null, $phone, [
            'customer_email' => Str::limit((string) $request->input('customer_email'), 250, '') ?: null,
            'delivery_type' => $pickup ? 'pickup' : 'home',
            'pickup_point' => $pickup ? (Str::limit((string) $request->input('pickup_point'), 250, '') ?: null) : null,
            'customer_area' => $area,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'coupon_code' => $cart->coupon()?->code,
            'delivery_charge' => $delivery,
            'total' => round($subtotal - $discount + $delivery, 2),
        ]);
    }

    public function captureLanding(Request $request, LandingPage $page): ?IncompleteOrder
    {
        $phone = self::normalisePhone($request->input('customer_phone'));

        if (! $phone) {
            return null;
        }

        $service = app(LandingPageOrder::class);
        $lines = $service->lines($page, $request->all());
        $area = $request->input('customer_area') === 'dhaka_outside' ? 'dhaka_outside' : 'dhaka_inside';
        $quote = $service->quote($page, $lines, $area);

        $items = array_map(fn (array $line) => [
            'product_id' => $line['item']->product_id,
            'variant_id' => $line['item']->product_variant_id,
            'product_name' => $line['item']->label(),
            'variant_name' => $line['item']->variant?->name,
            'quantity' => $line['quantity'],
            'price' => $line['item']->price(),
            'subtotal' => round($line['item']->price() * $line['quantity'], 2),
            'is_preorder' => false,
        ], $lines);

        return $this->store($request, 'landing', $page->id, $phone, [
            'customer_email' => Str::limit((string) $request->input('email'), 250, '') ?: null,
            'delivery_type' => 'home',
            'customer_area' => $area,
            'items' => $items,
            'subtotal' => $quote['subtotal'],
            'discount_amount' => $quote['discount'],
            'delivery_charge' => $quote['delivery'],
            'total' => $quote['total'],
        ]);
    }

    /**
     * The customer finished on their own: close every open lead from this
     * visitor, and any recent one under the same number from another device.
     */
    public function markConverted(Order $order): void
    {
        $phone = self::normalisePhone($order->customer_phone);
        $key = session(self::SESSION_KEY);

        IncompleteOrder::open()
            ->where(function ($query) use ($phone, $key) {
                $query->when($key, fn ($q) => $q->orWhere('visitor_key', $key))
                    ->when($phone, fn ($q) => $q->orWhere(fn ($q) => $q
                        ->where('customer_phone', $phone)
                        ->where('updated_at', '>=', now()->subDays(self::SAME_PERSON_DAYS))));
            })
            ->when(! $phone && ! $key, fn ($q) => $q->whereRaw('1 = 0'))
            ->update(['status' => 'converted', 'order_id' => $order->id, 'updated_at' => now()]);
    }

    /**
     * The customer agreed on the phone: turn the saved lines into a real order
     * and take the stock, exactly as the checkout would have.
     *
     * @throws RuntimeException when an item is gone or out of stock
     */
    public function convert(IncompleteOrder $lead): Order
    {
        if ($lead->isConverted()) {
            throw new RuntimeException('This lead has already been converted.');
        }

        if (empty($lead->items)) {
            throw new RuntimeException('There are no products saved on this lead.');
        }

        return DB::transaction(function () use ($lead) {
            $hasPreorder = collect($lead->items)->contains(fn ($item) => ! empty($item['is_preorder']));

            $order = Order::create([
                'user_id' => $lead->user_id,
                'customer_name' => $lead->customer_name ?: 'Customer',
                'customer_phone' => $lead->customer_phone,
                'customer_email' => $lead->customer_email,
                'customer_address' => $lead->delivery_type === 'pickup'
                    ? 'Store Pickup'
                    : ($lead->customer_address ?: 'Address to be confirmed by phone'),
                'customer_area' => $lead->customer_area,
                'pickup_point' => $lead->pickup_point,
                'notes' => $lead->notes,
                'subtotal' => $lead->subtotal,
                'discount_amount' => $lead->discount_amount,
                'coupon_code' => $lead->coupon_code,
                'delivery_charge' => $lead->delivery_charge,
                'total' => $lead->total,
                'payment_method' => 'cod',
                'source' => $lead->source === 'landing' ? 'landing' : 'website',
                'landing_page_id' => $lead->landing_page_id,
                'has_preorder' => $hasPreorder,
            ]);

            foreach ($lead->items as $item) {
                $variant = ProductVariant::with('product', 'comboItems.component')->find($item['variant_id']);

                if (! $variant) {
                    throw new RuntimeException("{$item['product_name']} is no longer available.");
                }

                if (empty($item['is_preorder'])) {
                    $this->inventory->deduct($variant, (int) $item['quantity']);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'] ?? null,
                    'quantity' => $item['quantity'],
                    'is_preorder' => ! empty($item['is_preorder']),
                    'unit_price' => $item['price'],
                    'total' => $item['subtotal'],
                ]);
            }

            $lead->update([
                'status' => 'converted',
                'order_id' => $order->id,
                'handled_by' => auth()->id(),
            ]);

            return $order;
        });
    }

    private function store(Request $request, string $source, ?int $landingPageId, string $phone, array $data): IncompleteOrder
    {
        $key = $this->visitorKey();

        // A save still in flight when the order went through must not reopen
        // the lead the order just closed.
        $justOrdered = IncompleteOrder::where('visitor_key', $key)
            ->where('source', $source)
            ->where('landing_page_id', $landingPageId)
            ->where('customer_phone', $phone)
            ->where('status', 'converted')
            ->where('updated_at', '>=', now()->subMinutes(10))
            ->latest('id')
            ->first();

        if ($justOrdered) {
            return $justOrdered;
        }

        // A converted lead is history; a new attempt starts a new row.
        $lead = IncompleteOrder::open()
            ->where('visitor_key', $key)
            ->where('source', $source)
            ->where('landing_page_id', $landingPageId)
            ->latest('id')
            ->first() ?? new IncompleteOrder([
                'visitor_key' => $key,
                'source' => $source,
                'landing_page_id' => $landingPageId,
                'status' => 'new',
            ]);

        $lead->fill($data + [
            'user_id' => auth()->id(),
            'customer_phone' => $phone,
            'customer_name' => Str::limit((string) $request->input('customer_name'), 250, '') ?: null,
            'customer_address' => Str::limit((string) $request->input('customer_address'), 1000, '') ?: null,
            'notes' => Str::limit((string) $request->input('notes'), 1000, '') ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
        ]);

        // Eloquent skips the write when nothing changed, so updated_at stays
        // the time the customer last actually typed something.
        $lead->save();

        return $lead;
    }
}
