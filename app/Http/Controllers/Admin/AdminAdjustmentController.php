<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\PresentsVariantOptions;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Adjustment;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAdjustmentController extends Controller
{
    use BulkDeletes;
    use PresentsVariantOptions;
    use SearchesRecords;

    public function index(Request $request)
    {
        $adjustments = $this->applySearch(
            Adjustment::with('productVariant.product'),
            $request->input('search'),
            ['reason', 'type', 'productVariant.name', 'productVariant.product.name']
        )
            ->latest('adjustment_date')
            ->paginate(20)
            ->withQueryString();

        return view('admin.adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $variants = $this->variantOptions();

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

            $validated['type'] === Adjustment::RETURNED
                ? $variant->increment('stock', $validated['quantity'])
                : $variant->decrement('stock', $validated['quantity']);
        });

        return redirect()->route('admin.adjustments.index')->with('success', 'Stock adjustment recorded.');
    }

    public function destroy(Adjustment $adjustment)
    {
        // Adjustment::booted() puts the stock back.
        $adjustment->delete();

        return redirect()->route('admin.adjustments.index')->with('success', 'Adjustment deleted and stock reverted.');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, Adjustment::class);

        return $this->bulkResponse($result, 'adjustments', 'admin.adjustments.index');
    }
}
