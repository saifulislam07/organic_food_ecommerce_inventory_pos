<?php

namespace App\Http\Controllers\Admin;

use App\Courier\CourierManager;
use App\Http\Controllers\Admin\Concerns\PresentsSellableVariants;
use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\CourierDispatch;
use App\Services\InventoryService;
use App\Services\OrderWorkflow;
use App\Support\CourierSettings;
use App\Support\PaymentAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class AdminOrderController extends Controller
{
    use PresentsSellableVariants;
    use SortsRecords;

    public function __construct(
        private readonly OrderWorkflow $workflow,
        private readonly CourierDispatch $dispatch,
        private readonly CourierManager $couriers,
    ) {}

    public function index(Request $request)
    {
        $query = Order::with('items', 'landingPage');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Which channel the order came through: the website, the counter, or a
        // campaign landing page.
        if ($request->filled('source') && array_key_exists($request->source, Order::SOURCES)) {
            $query->where('source', $request->source);
        }

        if ($request->filled('courier')) {
            $query->where('courier', $request->courier);
        }

        // Orders carrying something not yet in stock. "waiting" is the queue
        // that matters: the ones nobody has shipped or cancelled yet.
        if ($request->input('preorder') === 'waiting') {
            $query->where('has_preorder', true)->whereNotIn('status', Order::CLOSED);
        } elseif ($request->input('preorder') === 'all') {
            $query->where('has_preorder', true);
        }

        // Delivered but never settled is the queue that actually costs money:
        // every row in it is an order the books are still valuing at its face
        // amount because nobody has said what the courier handed over.
        if ($request->input('settlement') === 'pending') {
            $query->where('status', 'delivered')->whereNull('collected_amount');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('courier_consignment_id', 'like', "%{$search}%")
                    ->orWhere('courier_tracking_code', 'like', "%{$search}%");
            });
        }

        $this->applySort($query, $request, [
            'order_number' => 'order_number',
            'created_at' => 'created_at',
            'customer_name' => 'customer_name',
            'total' => 'total',
            'collected_amount' => 'collected_amount',
            'courier' => 'courier',
            'source' => 'source',
            'status' => 'status',
        ], 'created_at');

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'couriers' => $this->couriers,
            'courierOptions' => CourierSettings::driverClasses(),
            'awaitingSettlement' => Order::where('status', 'delivered')->whereNull('collected_amount')->count(),
            'awaitingPreorder' => Order::where('has_preorder', true)
                ->whereNotIn('status', Order::CLOSED)
                ->count(),
        ]);
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'landingPage', 'user');

        return view('admin.orders.show', [
            'order' => $order,
            'couriers' => $this->couriers,
            'usableCouriers' => $this->couriers->usable(),
        ]);
    }

    public function edit(Order $order)
    {
        $order->load('items.variant.product', 'items.product');

        return view('admin.orders.edit', [
            'order' => $order,
            'lines' => $order->items->map(fn (OrderItem $item) => [
                'variant_id' => $item->product_variant_id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                // What could be sold if this line went back on the shelf: the
                // stock on hand plus what this order is already holding, or the
                // editor would refuse to let anyone raise a quantity that the
                // order itself is the reason there is no stock for.
                'stock' => $item->variant
                    ? $item->variant->available_stock + (int) $item->quantity
                    : null,
            ])->values(),
        ]);
    }

    /**
     * Save an edited order.
     *
     * Rewriting the lines means rewriting the stock behind them, and the only
     * safe way to do that is to put everything the order is currently holding
     * back on the shelf and then take what the new lines need. Both halves are
     * in one transaction: a failure part way through would otherwise leave the
     * shop with phantom stock it can never sell.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_address' => ['required', 'string', 'max:2000'],
            'customer_area' => ['nullable', 'string', 'max:255'],
            'pickup_point' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(PaymentAccounts::keys())],
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'delivery_charge' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(['flat', 'percent'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($order, $validated) {
                $this->rewriteLines($order, $validated['items']);

                $subtotal = round($order->items()->sum('total'), 2);
                $discount = $this->discountFor($validated, $subtotal);
                $delivery = round((float) $validated['delivery_charge'], 2);

                $order->fill([
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'customer_address' => $validated['customer_address'],
                    'customer_area' => $validated['customer_area'] ?? null,
                    'pickup_point' => $validated['pickup_point'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'payment_method' => $validated['payment_method'],
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'delivery_charge' => $delivery,
                    'total' => max(0, round($subtotal + $delivery - $discount, 2)),
                ])->save();

                // Last, and through the workflow, so an edit that also moves the
                // order tells the customer exactly as the status dropdown would.
                $this->workflow->changeStatus($order, $validated['status']);
            });
        } catch (RuntimeException $e) {
            // Not enough stock for the new lines. The transaction has already
            // rolled back, so the order and the shelves are as they were.
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order '.$order->order_number.' updated.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate(['status' => ['required', Rule::in(array_keys(Order::STATUSES))]]);

        $this->workflow->changeStatus($order, $request->status);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order status updated!',
                'status' => $order->status,
                'updated_at' => $order->updated_at->format('d M Y, h:i A'),
                // Delivered without a settlement is a prompt, not an error — the
                // control tells the operator there is a figure still to record.
                'needs_settlement' => $order->status === 'delivered' && ! $order->isSettled(),
            ]);
        }

        return back()->with('success', 'Order status updated!');
    }

    /**
     * Record what the delivery settled for: money in, and what the courier kept.
     */
    public function settle(Request $request, Order $order)
    {
        $validated = $request->validate([
            'collected_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'collected_in' => ['required', Rule::in(PaymentAccounts::keys())],
            'courier_charge' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'settlement_note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->workflow->settle($order, $validated);

        $variance = $order->settlement_variance;

        $message = 'Settlement recorded for '.$order->order_number.'.';

        // Said out loud rather than left to be noticed: a short settlement is
        // the thing this whole form exists to catch.
        if ($variance !== null && abs($variance) >= 0.01) {
            $message .= $variance < 0
                ? ' Short by ৳'.number_format(abs($variance), 2).' against what was expected.'
                : ' ৳'.number_format($variance, 2).' more than expected.';
        }

        return back()->with('success', $message);
    }

    /** Hand the parcel to a courier. */
    public function sendToCourier(Request $request, Order $order)
    {
        $validated = $request->validate([
            'courier' => ['required', Rule::in(array_keys(CourierSettings::driverClasses()))],
            'consignment_id' => ['nullable', 'string', 'max:64'],
            'tracking_code' => ['nullable', 'string', 'max:64'],
        ]);

        $result = $this->dispatch->book($order, $validated['courier'], [
            'consignment_id' => $validated['consignment_id'] ?? null,
            'tracking_code' => $validated['tracking_code'] ?? null,
        ]);

        if (! $result->booked) {
            return back()->withErrors(['courier' => $result->error]);
        }

        return back()->with(
            'success',
            $this->couriers->label($order->courier).' accepted the parcel'.
            ($order->courier_tracking_code ? ' (tracking '.$order->courier_tracking_code.')' : '').'.'
        );
    }

    /** Ask the courier where the parcel is, and move the order if it says so. */
    public function syncCourier(Order $order)
    {
        $before = $order->status;
        $result = $this->dispatch->sync($order);

        if (! $result->found) {
            return back()->withErrors(['courier' => $result->error]);
        }

        $message = $this->couriers->label($order->courier).' says: '.$result->status.'.';

        $message .= $order->status !== $before
            ? ' Order moved to '.$order->status_label.'.'
            : ' Order status unchanged.';

        return back()->with('success', $message);
    }

    /** Catalogue search for the order editor. */
    public function products(Request $request)
    {
        $query = trim((string) $request->get('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        return response()->json($this->searchVariants($query));
    }

    public function invoice(Order $order)
    {
        $order->load('items.product');

        return view('admin.orders.invoice', compact('order'));
    }

    /**
     * Put back what the order was holding, then take what the new lines need.
     *
     * @param  array<int, array{variant_id: int, quantity: int, unit_price: float}>  $lines
     */
    private function rewriteLines(Order $order, array $lines): void
    {
        $inventory = app(InventoryService::class);

        $order->load('items.variant.comboItems.component');

        foreach ($order->items as $item) {
            // A line whose variant was deleted has nothing to give back; the
            // stock it was holding went with the variant.
            $item->variant && $inventory->restore($item->variant, (int) $item->quantity);
        }

        $order->items()->delete();

        // Two lines for the same variant would each be checked against stock on
        // their own and could together exceed it, so they are merged first — at
        // the price the last one asked for, which is the one just edited.
        $merged = [];

        foreach ($lines as $line) {
            $id = (int) $line['variant_id'];

            $merged[$id] = [
                'quantity' => ($merged[$id]['quantity'] ?? 0) + (int) $line['quantity'],
                'unit_price' => round((float) $line['unit_price'], 2),
            ];
        }

        foreach ($merged as $variantId => $line) {
            $variant = ProductVariant::with('product', 'comboItems.component')->findOrFail($variantId);

            $inventory->deduct($variant, $line['quantity']);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'total' => round($line['unit_price'] * $line['quantity'], 2),
            ]);
        }

        $order->unsetRelation('items');
    }

    /**
     * The money a discount is worth, whichever way it was expressed.
     *
     * Capped at the subtotal either way: an order can be given away, but it
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
