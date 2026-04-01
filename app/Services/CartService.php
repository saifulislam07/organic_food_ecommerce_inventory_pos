<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Session;

use App\Models\Setting;

class CartService
{
    private string $sessionKey = 'cart';

    public function getItems(): array
    {
        return Session::get($this->sessionKey, []);
    }

    public function add(int $productId, int $variantId, int $quantity = 1): array
    {
        $cart = $this->getItems();
        $key = $productId . '_' . $variantId;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $product = Product::find($productId);
            $variant = ProductVariant::find($variantId);

            if (!$product || !$variant) {
                return ['success' => false, 'message' => 'Product not found'];
            }

            $cart[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'price' => $variant->display_price,
                'original_price' => $variant->price,
                'image' => $product->image_url,
                'quantity' => $quantity,
                'weight_kg' => $variant->weight_kg,
            ];
        }

        $cart[$key]['subtotal'] = $cart[$key]['price'] * $cart[$key]['quantity'];
        Session::put($this->sessionKey, $cart);

        return ['success' => true, 'message' => 'Added to cart', 'cart_count' => $this->count()];
    }

    public function update(string $key, int $quantity): array
    {
        $cart = $this->getItems();

        if (!isset($cart[$key])) {
            return ['success' => false, 'message' => 'Item not found in cart'];
        }

        if ($quantity <= 0) {
            return $this->remove($key);
        }

        $cart[$key]['quantity'] = $quantity;
        $cart[$key]['subtotal'] = $cart[$key]['price'] * $quantity;
        Session::put($this->sessionKey, $cart);

        return ['success' => true, 'message' => 'Cart updated', 'cart_count' => $this->count()];
    }

    public function remove(string $key): array
    {
        $cart = $this->getItems();
        unset($cart[$key]);
        Session::put($this->sessionKey, $cart);

        return ['success' => true, 'message' => 'Item removed', 'cart_count' => $this->count()];
    }

    public function getSubtotal(): float
    {
        $items = $this->getItems();
        return array_sum(array_column($items, 'subtotal'));
    }

    public function getDeliveryCharge($area = 'dhaka_inside'): float
    {
        $subtotal = $this->getSubtotal();
        $threshold = (float) Setting::get('free_delivery_threshold', 2000);

        if ($subtotal >= $threshold) {
            return 0;
        }

        if ($area === 'dhaka_outside') {
            return (float) Setting::get('shipping_fee_outside', 120);
        }

        return (float) Setting::get('shipping_fee_inside', 60);
    }

    public function getTotal($area = 'dhaka_inside'): float
    {
        return $this->getSubtotal() + $this->getDeliveryCharge($area);
    }

    public function count(): int
    {
        $items = $this->getItems();
        return array_sum(array_column($items, 'quantity'));
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    public function isEmpty(): bool
    {
        return empty($this->getItems());
    }
}
