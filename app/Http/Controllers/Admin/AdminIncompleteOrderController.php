<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\IncompleteOrder;
use App\Services\IncompleteOrderTracker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Checkouts that were started and abandoned, as a call list: who to ring, what
 * they wanted, and what they said when they picked up.
 */
class AdminIncompleteOrderController extends Controller
{
    use BulkDeletes, SearchesRecords;
    use SortsRecords;

    public function index(Request $request)
    {
        $status = $request->input('status', 'open');

        $leads = $this->applySearch(
            IncompleteOrder::query()->with('landingPage:id,internal_name,slug', 'handler:id,name', 'order:id,order_number'),
            $request->input('search'),
            ['customer_name', 'customer_phone', 'customer_address']
        )
            ->when($status === 'open', fn ($q) => $q->open())
            ->when(array_key_exists($status, IncompleteOrder::STATUSES), fn ($q) => $q->where('status', $status))
            ->when($request->input('source'), fn ($q, $source) => $q->where('source', $source))
            ->when($request->input('from'), fn ($q, $from) => $q->whereDate('updated_at', '>=', $from))
            ->when($request->input('to'), fn ($q, $to) => $q->whereDate('updated_at', '<=', $to));

        $this->applySort($leads, $request, [
            'customer_name' => 'customer_name',
            'total' => 'total',
            'updated_at' => 'updated_at',
        ], 'updated_at');

        $leads = $leads->paginate(20)->withQueryString();

        $counts = IncompleteOrder::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.incomplete-orders.index', compact('leads', 'status', 'counts'));
    }

    /** Status and the call note, saved together from the row. */
    public function update(Request $request, IncompleteOrder $incompleteOrder)
    {
        if ($incompleteOrder->isConverted()) {
            return back()->with('error', 'This lead is already an order.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(IncompleteOrder::MANUAL_STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $incompleteOrder->update($validated + [
            'handled_by' => $request->user()->id,
            'called_at' => $validated['status'] === 'new' ? $incompleteOrder->called_at : now(),
        ]);

        return back()->with('success', 'Lead updated.');
    }

    /** The customer agreed on the phone — make the order and open it for checking. */
    public function convert(IncompleteOrder $incompleteOrder, IncompleteOrderTracker $tracker)
    {
        try {
            $order = $tracker->convert($incompleteOrder);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Order {$order->order_number} created from the incomplete checkout. Check the address and items before confirming.");
    }

    public function destroy(IncompleteOrder $incompleteOrder)
    {
        $incompleteOrder->delete();

        return redirect()->route('admin.incomplete-orders.index')->with('success', 'Lead deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, IncompleteOrder::class);

        return $this->bulkResponse($result, 'leads', 'admin.incomplete-orders.index');
    }
}
