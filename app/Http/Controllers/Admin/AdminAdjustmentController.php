<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Adjustment;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAdjustmentController extends Controller
{
    public function index()
    {
        $adjustments = Adjustment::with('productVariant.product')
            ->latest('adjustment_date')
            ->paginate(20);
        return view('admin.adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $variants = ProductVariant::with('product')->get();
        return view('admin.adjustments.create', compact('variants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:lost,damage,returned,other',
            'reason' => 'nullable|string',
            'adjustment_date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated) {
            Adjustment::create($validated);
            
            $variant = ProductVariant::find($validated['product_variant_id']);
            // Reductions except for 'returned' which might increase stock depending on business logic
            // For now, let's assume 'returned' increases and others decrease
            if ($validated['type'] == 'returned') {
                $variant->increment('stock', $validated['quantity']);
            } else {
                $variant->decrement('stock', $validated['quantity']);
            }
        });

        return redirect()->route('admin.adjustments.index')->with('success', 'Stock adjustment recorded.');
    }

    public function destroy(Adjustment $adjustment)
    {
        DB::transaction(function () use ($adjustment) {
            $variant = $adjustment->productVariant;
            if ($adjustment->type == 'returned') {
                $variant->decrement('stock', $adjustment->quantity);
            } else {
                $variant->increment('stock', $adjustment->quantity);
            }
            $adjustment->delete();
        });

        return redirect()->route('admin.adjustments.index')->with('success', 'Adjustment deleted and stock reverted.');
    }
}
