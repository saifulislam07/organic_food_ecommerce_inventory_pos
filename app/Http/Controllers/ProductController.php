<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->with('variants', 'category')->active()->firstOrFail();
        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('variants')
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'related'));
    }
}
