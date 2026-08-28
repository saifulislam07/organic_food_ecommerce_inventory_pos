<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class AdminInventoryController extends Controller
{
    use SearchesRecords;

    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request)
    {
        $variants = $this->applySearch(
            ProductVariant::with('product', 'comboItems.component'),
            $request->input('search'),
            ['name', 'sku', 'product.name']
        )
            ->orderBy('stock', 'asc')
            ->paginate(30)
            ->withQueryString();

        $rows = $variants->getCollection()
            ->filter(fn (ProductVariant $variant) => $variant->product !== null)
            ->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'image' => $variant->product->image_url,
                'stock' => (int) $variant->stock,
                'price' => (float) $variant->price,
                'update_url' => route('admin.inventory.update', $variant),
            ])
            ->values();

        $lowStockCount = ProductVariant::where('stock', '<', self::LOW_STOCK_THRESHOLD)->count();

        return view('admin.inventory.index', [
            'variants' => $variants,
            'rows' => $rows,
            'lowStockCount' => $lowStockCount,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    public function updateStock(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $variant->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully.',
                'new_stock' => (int) $variant->stock,
            ]);
        }

        return redirect()->back()->with('success', 'Stock updated successfully.');
    }
}
