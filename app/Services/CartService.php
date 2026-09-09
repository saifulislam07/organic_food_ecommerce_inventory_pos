<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Support\Facades\Session;

/**
 * The session cart, and the prices that come out of it.
 *
 * Lines are stored with only what identifies them; every price is worked out
 * again on read from the live variant, so an admin changing a price or ending
 * an offer is reflected in a cart that was filled yesterday — and so a coupon
 * applied now can re-price lines that were added before it.
 */
class CartService
{
    private string $sessionKey = 'cart';

    private string $couponKey = 'cart_coupon';

    /** Resolved once per request; the cart is priced several times per page. */
    private ?Coupon $resolvedCoupon = null;

    private bool $couponResolved = false;

    /**
     * The priced lines, held for the life of the request.
     *
     * A single page asks for the subtotal, the discount, the delivery and the
     * total, and each of those walks the whole cart. Without this the variant
     * lookup would run five times over.
     */
    private ?array $pricedCache = null;

    /* ---------------------------------------------------------- the lines */

    /** The raw session rows, before any pricing. */
    private function rows(): array
    {
        return Session::get($this->sessionKey, []);
    }

    public function getItems(): array
    {
        return $this->pricedCache ??= $this->priced($this->rows(), $this->coupon());
    }

    /** Anything that changes the lines or the code invalidates the pricing. */
    private function flush(): void
    {
        $this->pricedCache = null;
    }

    public function add(int $productId, int $variantId, int $quantity = 1): array
    {
        $cart = $this->rows();
        $key = $productId.'_'.$variantId;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $product = Product::find($productId);
            $variant = ProductVariant::find($variantId);

            if (! $product || ! $variant) {
                return ['success' => false, 'message' => 'Product not found'];
            }

            $cart[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'image' => $product->image_url,
                'quantity' => $quantity,
                'weight_kg' => $variant->weight_kg,
            ];
        }

        Session::put($this->sessionKey, $cart);
        $this->flush();

        return ['success' => true, 'message' => 'Added to cart', 'cart_count' => $this->count()];
    }

    public function update(string $key, int $quantity): array
    {
        $cart = $this->rows();

        if (! isset($cart[$key])) {
            return ['success' => false, 'message' => 'Item not found in cart'];
        }

        if ($quantity <= 0) {
            return $this->remove($key);
        }

        $cart[$key]['quantity'] = $quantity;
        Session::put($this->sessionKey, $cart);
        $this->flush();

        return ['success' => true, 'message' => 'Cart updated', 'cart_count' => $this->count()];
    }

    public function remove(string $key): array
    {
        $cart = $this->rows();
        unset($cart[$key]);
        Session::put($this->sessionKey, $cart);
        $this->flush();

        return ['success' => true, 'message' => 'Item removed', 'cart_count' => $this->count()];
    }

    /* -------------------------------------------------------- the pricing */

    /**
     * Fill in each line's prices.
     *
     * Three numbers per line, and the difference between them is the whole
     * feature: `original_price` is the list price, `price` is what the product's
     * own offer asks, and `payable_price` is what is actually charged once the
     * coupon has been compared against that offer.
     */
    private function priced(array $cart, ?Coupon $coupon): array
    {
        if (! $cart) {
            return [];
        }

        $variants = ProductVariant::with('product')
            ->findMany(array_column($cart, 'variant_id'))
            ->keyBy('id');

        foreach ($cart as $key => $item) {
            $variant = $variants->get($item['variant_id']);

            // A variant deleted since it went in the cart keeps whatever it was
            // stored with; checkout refuses the order rather than guessing.
            $list = (float) ($variant?->price ?? $item['original_price'] ?? 0);
            $offer = (float) ($variant?->display_price ?? $list);

            $offerCut = max(0.0, $list - $offer);
            $couponCut = $coupon && $coupon->coversProduct($variant?->product)
                ? $coupon->discountPerUnit($list)
                : 0.0;

            // The rule: the bigger cut wins outright, they never add up.
            $winningCut = max($offerCut, $couponCut);
            $payable = round($list - $winningCut, 2);
            $quantity = (int) $item['quantity'];

            $cart[$key] = array_merge($item, [
                'original_price' => $list,
                'price' => $offer,
                'payable_price' => $payable,
                // What the coupon saved beyond the offer already in place — zero
                // when the product's own discount was the better of the two.
                'coupon_saving' => round(max(0.0, $offer - $payable) * $quantity, 2),
                'coupon_applied' => $couponCut > $offerCut,
                'subtotal' => round($offer * $quantity, 2),
                'payable_subtotal' => round($payable * $quantity, 2),
            ]);
        }

        return $cart;
    }

    /**
     * Subtotal at the products' own offer prices — what the order records and
     * what the summary shows above the coupon line.
     */
    public function getSubtotal(): float
    {
        return round(array_sum(array_column($this->getItems(), 'subtotal')), 2);
    }

    /** What the applied coupon takes off, over and above the offers already there. */
    public function getDiscount(): float
    {
        return round(array_sum(array_column($this->getItems(), 'coupon_saving')), 2);
    }

    /** Subtotal less the coupon: the goods total the shopper actually pays. */
    public function getPayableSubtotal(): float
    {
        return round($this->getSubtotal() - $this->getDiscount(), 2);
    }

    public function getDeliveryCharge($area = 'dhaka_inside'): float
    {
        $threshold = (float) Setting::get('free_delivery_threshold', 2000);

        // Measured on what is actually being paid, so a coupon can drop an order
        // back under the free-delivery bar rather than riding on the list price.
        if ($this->getPayableSubtotal() >= $threshold) {
            return 0;
        }

        if ($area === 'dhaka_outside') {
            return (float) Setting::get('shipping_fee_outside', 120);
        }

        return (float) Setting::get('shipping_fee_inside', 60);
    }

    public function getTotal($area = 'dhaka_inside'): float
    {
        return round($this->getPayableSubtotal() + $this->getDeliveryCharge($area), 2);
    }

    /* --------------------------------------------------------- the coupon */

    /**
     * The code held on this session, or null.
     *
     * Re-checked on every read rather than trusted from when it was typed: a
     * code can expire, run out or stop matching anything while the cart sits
     * open, and none of those may reach the total unnoticed.
     */
    public function coupon(): ?Coupon
    {
        if ($this->couponResolved) {
            return $this->resolvedCoupon;
        }

        $this->couponResolved = true;
        $coupon = Coupon::findByCode(Session::get($this->couponKey));

        if (! $coupon) {
            $this->resolvedCoupon = null;

            return null;
        }

        if ($coupon->rejectionReason(auth()->user(), $this->offerSubtotal()) !== null) {
            $this->forgetCoupon();
            $this->couponResolved = true;

            return null;
        }

        return $this->resolvedCoupon = $coupon;
    }

    /**
     * Try to put a code on this cart.
     *
     * @return array{success: bool, reason: ?string, discount: float}
     */
    public function applyCoupon(?string $code): array
    {
        $coupon = Coupon::findByCode($code);

        if (! $coupon) {
            return ['success' => false, 'reason' => 'not_found', 'discount' => 0.0];
        }

        if ($reason = $coupon->rejectionReason(auth()->user(), $this->offerSubtotal())) {
            return ['success' => false, 'reason' => $reason, 'discount' => 0.0];
        }

        Session::put($this->couponKey, $coupon->code);
        $this->couponResolved = false;
        $this->flush();

        // A code that matches nothing in the cart, or that every line already
        // beats with its own offer, is refused rather than shown saving ৳0.
        if ($this->getDiscount() <= 0) {
            $this->forgetCoupon();

            return ['success' => false, 'reason' => 'no_saving', 'discount' => 0.0];
        }

        return ['success' => true, 'reason' => null, 'discount' => $this->getDiscount()];
    }

    /**
     * The subtotal at offer prices, worked out without consulting any coupon.
     *
     * Deciding whether a code may be used needs a subtotal, and that subtotal
     * cannot itself depend on the code — hence a pass with no coupon at all.
     */
    private function offerSubtotal(): float
    {
        return round(array_sum(array_column($this->priced($this->rows(), null), 'subtotal')), 2);
    }

    public function forgetCoupon(): void
    {
        Session::forget($this->couponKey);
        $this->resolvedCoupon = null;
        $this->couponResolved = false;
        $this->flush();
    }

    /* ----------------------------------------------------------- the rest */

    public function count(): int
    {
        return array_sum(array_column($this->rows(), 'quantity'));
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey);
        $this->forgetCoupon();
        $this->flush();
    }

    public function isEmpty(): bool
    {
        return empty($this->rows());
    }
}
