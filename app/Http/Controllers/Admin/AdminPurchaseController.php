<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\PresentsVariantOptions;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Support\PaymentAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminPurchaseController extends Controller
{
    use BulkDeletes;
    use PresentsVariantOptions;
    use SearchesRecords;

    public function index(Request $request)
    {
        $purchases = $this->applySearch(
            Purchase::with(['supplier', 'productVariant.product']),
            $request->input('search'),
            ['notes', 'supplier.name', 'productVariant.name', 'productVariant.product.name']
        )
            ->latest('purchase_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $variants = $this->variantOptions();

        return view('admin.purchases.create', compact('suppliers', 'variants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'purchase_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            // The form always sends it; a script or an older integration may not.
            'paid_from' => ['nullable', Rule::in(PaymentAccounts::keys())],
        ]);

        $validated['paid_from'] = $validated['paid_from'] ?? PaymentAccounts::DEFAULT_PAYOUT;

        DB::transaction(function () use ($validated) {
            $purchase = Purchase::create($validated);

            // Update Variant Stock and Cost Price
            $variant = ProductVariant::find($validated['product_variant_id']);
            $variant->increment('stock', $validated['quantity']);
            $variant->update(['cost_price' => $validated['purchase_price']]);
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase recorded and stock updated.');
    }

    public function destroy(Purchase $purchase)
    {
        // Purchase::booted() puts the stock back.
        $purchase->delete();

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase deleted and stock reverted.');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, Purchase::class);

        return $this->bulkResponse($result, 'purchases', 'admin.purchases.index');
    }
}
