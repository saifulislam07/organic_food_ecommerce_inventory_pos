<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected CartService $cart;

    public function __construct(CartService $cart)
    {
        $this->cart = $cart;
    }

    public function index()
    {
        $items = $this->cart->getItems();
        $subtotal = $this->cart->getSubtotal();
        $discount = $this->cart->getDiscount();
        $delivery = $this->cart->getDeliveryCharge();
        $total = $this->cart->getTotal();
        $coupon = $this->cart->coupon();

        return view('cart.index', compact('items', 'subtotal', 'discount', 'delivery', 'total', 'coupon'));
    }

    /**
     * Put a discount code on the cart.
     *
     * Refusals come back as a reason key rather than a sentence; the storefront
     * owns the wording, in whichever language the shopper is reading.
     */
    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string|max:40']);

        $result = $this->cart->applyCoupon($request->input('code'));

        return response()->json(array_merge($result, $this->totals()));
    }

    public function removeCoupon()
    {
        $this->cart->forgetCoupon();

        return response()->json(array_merge(['success' => true], $this->totals()));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $result = $this->cart->add(
            $request->product_id,
            $request->variant_id,
            $request->get('quantity', 1)
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'quantity' => 'required|integer|min:0',
        ]);

        $result = $this->cart->update($request->key, $request->quantity);

        if ($request->expectsJson()) {
            return response()->json(array_merge($result, $this->totals()));
        }

        return back()->with('success', $result['message']);
    }

    public function remove(Request $request)
    {
        $request->validate(['key' => 'required|string']);

        $result = $this->cart->remove($request->key);

        if ($request->expectsJson()) {
            return response()->json(array_merge($result, $this->totals()));
        }

        return back()->with('success', $result['message']);
    }

    public function count()
    {
        return response()->json(['count' => $this->cart->count()]);
    }

    public function mini()
    {
        return response()->json(array_merge(['count' => $this->cart->count()], $this->totals()));
    }

    /**
     * The numbers every cart response carries. Shared so a new one — the coupon
     * discount, say — cannot reach one endpoint and not the others.
     */
    private function totals(): array
    {
        $coupon = $this->cart->coupon();

        return [
            'items' => $this->cart->getItems(),
            'subtotal' => $this->cart->getSubtotal(),
            'discount' => $this->cart->getDiscount(),
            'delivery' => $this->cart->getDeliveryCharge(),
            'total' => $this->cart->getTotal(),
            'coupon' => $coupon ? ['code' => $coupon->code, 'label' => $coupon->label] : null,
        ];
    }
}
