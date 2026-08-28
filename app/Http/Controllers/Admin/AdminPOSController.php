<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Support\OrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPOSController extends Controller
{
    public function index()
    {
        $items = ProductVariant::with('product')
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (ProductVariant $variant) => $variant->product !== null)
            ->map(fn (ProductVariant $variant) => $this->presentVariant($variant))
            ->values();

        return view('admin.pos.index', compact('items'));
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q'));

        $variants = ProductVariant::with('product')
            ->where(function ($builder) use ($query) {
                $builder
                    ->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json(
            $variants
                ->filter(fn (ProductVariant $variant) => $variant->product !== null)
                ->map(fn (ProductVariant $variant) => $this->presentVariant($variant))
                ->values()
        );
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
                $variant = ProductVariant::with('product', 'comboItems.component')
                    ->find($itemData['variant_id']);

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

                app(InventoryService::class)->deduct($variant, (int) $itemData['quantity']);
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

            app(OrderNotifier::class)->placed($order->fresh('items'));

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'order_id' => $order->id,
                'redirect' => route('admin.orders.show', $order),
            ]);
        });
    }

    /**
     * Flat shape the POS Vue component consumes, for both the initial grid and
     * the search endpoint. Accessors like image_url are not serialised by
     * default, so they are spelled out here.
     */
    private function presentVariant(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'name' => $variant->name,
            'sku' => $variant->sku,
            'price' => (float) ($variant->sale_price ?? $variant->price),
            'stock' => $variant->available_stock,
            'product_name' => $variant->product->name,
            'image' => $variant->product->image_url,
        ];
    }
}
