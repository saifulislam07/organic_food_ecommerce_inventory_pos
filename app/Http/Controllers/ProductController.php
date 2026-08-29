<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->withCardData()
            // The combo breakdown names and pictures each part, which the card
            // data alone does not reach.
            ->with('images', 'variants.comboItems.component.product', 'variants.comboItems.component.unit')
            ->active()
            ->firstOrFail();
        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->withCardData()
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'related'));
    }
}
