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

        // Approved only, the same as the home page. The page shows these and
        // the schema marks up exactly these, so Google never reads a rating a
        // visitor cannot see.
        $approved = $product->reviews()->approved();
        $reviewCount = (clone $approved)->count();
        $reviewAverage = $reviewCount ? round((float) (clone $approved)->avg('rating'), 1) : null;
        $reviews = $approved->latest()->take(10)->get();

        return view('products.show', compact('product', 'related', 'reviews', 'reviewCount', 'reviewAverage'));
    }
}
