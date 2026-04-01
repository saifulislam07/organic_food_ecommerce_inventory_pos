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
        $delivery = $this->cart->getDeliveryCharge();
        $total = $this->cart->getTotal();

        return view('cart.index', compact('items', 'subtotal', 'delivery', 'total'));
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

        if ($request->ajax()) {
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

        if ($request->ajax()) {
            return response()->json(array_merge($result, [
                'subtotal' => $this->cart->getSubtotal(),
                'delivery' => $this->cart->getDeliveryCharge(),
                'total' => $this->cart->getTotal(),
                'items' => $this->cart->getItems(),
            ]));
        }

        return back()->with('success', $result['message']);
    }

    public function remove(Request $request)
    {
        $request->validate(['key' => 'required|string']);

        $result = $this->cart->remove($request->key);

        if ($request->ajax()) {
            return response()->json(array_merge($result, [
                'subtotal' => $this->cart->getSubtotal(),
                'delivery' => $this->cart->getDeliveryCharge(),
                'total' => $this->cart->getTotal(),
            ]));
        }

        return back()->with('success', $result['message']);
    }

    public function count()
    {
        return response()->json(['count' => $this->cart->count()]);
    }

    public function mini()
    {
        return response()->json([
            'items' => $this->cart->getItems(),
            'count' => $this->cart->count(),
            'subtotal' => $this->cart->getSubtotal(),
            'total' => $this->cart->getTotal(),
        ]);
    }
}
