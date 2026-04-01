<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with(['supplier', 'productVariant.product'])
            ->latest('purchase_date')
            ->paginate(20);
        return view('admin.purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $variants = ProductVariant::with('product')->get();
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
        ]);

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
        DB::transaction(function () use ($purchase) {
            // Revert stock
            $variant = $purchase->productVariant;
            $variant->decrement('stock', $purchase->quantity);
            $purchase->delete();
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase deleted and stock reverted.');
    }
}
