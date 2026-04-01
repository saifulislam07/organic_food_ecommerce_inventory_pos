<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class AdminInventoryController extends Controller
{
    public function index()
    {
        $variants = ProductVariant::with('product')
            ->orderBy('stock', 'asc')
            ->paginate(30);
            
        return view('admin.inventory.index', compact('variants'));
    }

    public function updateStock(Request $request, ProductVariant $variant)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $variant->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true, 
                'message' => 'Stock updated successfully.',
                'new_stock' => $variant->stock
            ]);
        }

        return redirect()->back()->with('success', 'Stock updated successfully.');
    }
}
