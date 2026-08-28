<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->withCardData()->with('images')->active()->firstOrFail();
        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->withCardData()
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'related'));
    }
}
