<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPOSController extends Controller
{
    public function index()
    {
        $products = Product::with('variants')->get();
        return view('admin.pos.index', compact('products'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $variants = ProductVariant::with('product')
            ->whereHas('product', function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orWhere('sku', 'like', "%{$query}%")
            ->get();

        return response()->json($variants);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_charge' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $subtotal = 0;
            $orderItems = [];

            foreach ($validated['items'] as $itemData) {
                $variant = ProductVariant::with('product')->find($itemData['variant_id']);
                
                if ($variant->stock < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$variant->product->name} ({$variant->name})");
                }

                $price = $variant->sale_price ?? $variant->price;
                $lineTotal = $price * $itemData['quantity'];
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $price,
                    'total' => $lineTotal,
                ];

                $variant->decrement('stock', $itemData['quantity']);
            }

            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'subtotal' => $subtotal,
                'discount_amount' => $validated['discount_amount'],
                'delivery_charge' => $validated['delivery_charge'],
                'total' => ($subtotal + $validated['delivery_charge']) - $validated['discount_amount'],
                'status' => 'confirmed',
                'payment_method' => 'cod', 
                'source' => 'pos',
            ]);

            foreach ($orderItems as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'order_id' => $order->id,
                'redirect' => route('admin.orders.show', $order)
            ]);
        });
    }
}
