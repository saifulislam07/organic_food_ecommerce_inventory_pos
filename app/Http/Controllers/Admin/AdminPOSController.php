<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\PresentsSellableVariants;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\InventoryService;
use App\Support\OrderNotifier;
use App\Support\PaymentAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminPOSController extends Controller
{
    use PresentsSellableVariants;

    public function index()
    {
        $items = ProductVariant::with('product.category', 'comboItems.component')
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (ProductVariant $variant) => $variant->product !== null)
            ->map(fn (ProductVariant $variant) => $this->presentVariant($variant))
            ->values();

        // The counter filters by category constantly, so the chips are built
        // from what is actually on the board rather than from every category.
        $categories = $items
            ->filter(fn (array $item) => $item['category_id'] !== null)
            ->groupBy('category_id')
            ->map(fn ($group, $id) => [
                'id' => (int) $id,
                'name' => $group->first()['category'],
                'count' => $group->count(),
            ])
            ->sortBy('name')
            ->values();

        return view('admin.pos.index', compact('items', 'categories'));
    }

    public function search(Request $request)
    {
        return response()->json($this->searchVariants(trim((string) $request->get('q'))));
    }

    /**
     * Registered customers matching what the cashier is typing.
     *
     * Phone first: at a counter the number is what someone gives you, and it is
     * what tells two people called Rahim apart.
     */
    public function customers(Request $request)
    {
        $query = trim((string) $request->get('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $customers = User::query()
            ->where('role', 'customer')
            ->where(fn ($q) => $q
                ->where('mobile', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%"))
            ->with(['addresses' => fn ($q) => $q->orderByDesc('is_default')])
            ->withCount('orders')
            ->limit(10)
            ->get();

        return response()->json($customers->map(fn (User $customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->mobile,
            'address' => $customer->addresses->first()?->address,
            'orders_count' => $customer->orders_count,
        ]));
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
            // Read as taka by default. With discount_type = percent it is a
            // percentage instead, and the server works out the money — the
            // browser never gets to say what the discount was worth.
            'discount_amount' => 'required|numeric|min:0',
            'discount_type' => ['nullable', Rule::in(['flat', 'percent'])],
            'payment_method' => ['nullable', Rule::in(PaymentAccounts::keys())],
            'customer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', Rule::in(Order::POS_SOURCES)],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($validated) {
            $subtotal = 0;
            // What the offers already on these products come to. The slip reads
            // as a shop slip does — everything at its shelf price, with the
            // saving broken out underneath — rather than hiding the reduction
            // inside the line and showing a discount of nothing.
            $offerSaving = 0;
            $orderItems = [];

            foreach ($validated['items'] as $itemData) {
                $variant = ProductVariant::with('product', 'comboItems.component')
                    ->find($itemData['variant_id']);

                $price = (float) $variant->price;
                $offerSaving += ($price - (float) ($variant->sale_price ?? $price)) * $itemData['quantity'];
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

            // The offers are a floor, not a suggestion: whatever the browser
            // asks for, a customer never pays more than the shelf price. The
            // cashier can still knock off more on top.
            $discount = max($this->discountFor($validated, $subtotal), round($offerSaving, 2));
            $discount = min($discount, $subtotal);
            $total = max(0, ($subtotal + $validated['delivery_charge']) - $discount);

            // Only a customer account, never an admin: attaching a staff login
            // to a walk-in sale would put it in that person's order history.
            $customer = isset($validated['customer_id'])
                ? User::where('role', 'customer')->find($validated['customer_id'])
                : null;

            $order = Order::create([
                'user_id' => $customer?->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'delivery_charge' => $validated['delivery_charge'],
                'total' => $total,
                // Below the total means a part payment, so it is kept as given
                // rather than rounded up to look settled.
                'paid_amount' => $validated['paid_amount'] ?? null,
                'status' => 'confirmed',
                'payment_method' => $validated['payment_method'] ?? PaymentAccounts::DEFAULT_POS,
                'source' => $validated['source'] ?? 'pos',
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
     * The money a discount is worth, whichever way the cashier expressed it.
     *
     * Capped at the subtotal either way: a sale can be given away, but it
     * cannot come out owing the customer money.
     */
    private function discountFor(array $validated, float $subtotal): float
    {
        $value = (float) $validated['discount_amount'];

        if (($validated['discount_type'] ?? 'flat') === 'percent') {
            $value = $subtotal * (min($value, 100) / 100);
        }

        return round(min($value, $subtotal), 2);
    }
}
