<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    protected CartService $cart;

    public function __construct(CartService $cart)
    {
        $this->cart = $cart;
    }

    public function index()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('shop')->with('error', 'Your cart is empty');
        }

        $items = $this->cart->getItems();
        $subtotal = $this->cart->getSubtotal();
        
        $shippingFeeInside = (float) \App\Models\Setting::get('shipping_fee_inside', 60);
        $shippingFeeOutside = (float) \App\Models\Setting::get('shipping_fee_outside', 120);
        $threshold = (float) \App\Models\Setting::get('free_delivery_threshold', 2000);
        
        $userAddresses = auth()->check() ? auth()->user()->addresses : collect([]);
        $defaultAddress = $userAddresses->where('is_default', true)->first() ?? $userAddresses->first();

        $delivery = $this->cart->getDeliveryCharge($defaultAddress->area ?? 'dhaka_inside');
        $total = $this->cart->getTotal($defaultAddress->area ?? 'dhaka_inside');

        return view('checkout.index', compact(
            'items', 'subtotal', 'delivery', 'total', 
            'shippingFeeInside', 'shippingFeeOutside', 'threshold',
            'userAddresses', 'defaultAddress'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required_if:delivery_type,home|string',
            'customer_area' => 'nullable|string|max:255',
            'delivery_type' => 'required|in:home,pickup',
            'pickup_point' => 'required_if:delivery_type,pickup|nullable|string|max:255',
            'notes' => 'nullable|string',
            'save_address' => 'nullable|string', // Checkbox comes as string 'on'
        ]);

        if ($this->cart->isEmpty()) {
            return redirect()->route('shop')->with('error', 'Your cart is empty');
        }

        if (auth()->check() && $request->filled('save_address')) {
            auth()->user()->addresses()->updateOrCreate(
                ['address' => $validated['customer_address'], 'area' => $validated['customer_area'] ?? null],
                [
                    'name' => $validated['customer_name'],
                    'phone' => $validated['customer_phone'],
                ]
            );
        }

        $deliveryCharge = $validated['delivery_type'] === 'pickup' ? 0 : $this->cart->getDeliveryCharge($validated['customer_area']);
        $subtotal = $this->cart->getSubtotal();

        $order = Order::create([
            'user_id' => auth()->id(),
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_address' => $validated['delivery_type'] === 'pickup' ? 'Store Pickup' : $validated['customer_address'],
            'customer_area' => $validated['customer_area'] ?? null,
            'pickup_point' => $validated['pickup_point'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'subtotal' => $subtotal,
            'delivery_charge' => $deliveryCharge,
            'total' => $subtotal + $deliveryCharge,
            'payment_method' => 'cod',
            'source' => 'website',
        ]);

        foreach ($this->cart->getItems() as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['variant_id'],
                'product_name' => $item['product_name'],
                'variant_name' => $item['variant_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total' => $item['subtotal'],
            ]);
        }

        $this->cart->clear();

        return redirect()->route('checkout.success', $order->order_number);
    }

    public function success(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->with('items')->firstOrFail();
        return view('checkout.success', compact('order'));
    }
}
