<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active()->with('variants', 'category');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_bn', 'like', "%{$search}%")
                    ->orWhere('short_description_en', 'like', "%{$search}%")
                    ->orWhere('short_description_bn', 'like', "%{$search}%");
            });
        }

        // Prices live on the variants, so order by each product's cheapest one.
        $lowestPrice = ProductVariant::query()
            ->selectRaw('MIN(COALESCE(sale_price, price))')
            ->whereColumn('product_variants.product_id', 'products.id');

        // Sort by the name actually shown, which is locale dependent.
        $displayName = Product::displayNameExpression();

        $sort = $request->get('sort', 'latest');
        $query = match ($sort) {
            'name_asc' => $query->orderBy($displayName),
            'name_desc' => $query->orderByDesc($displayName),
            'price_low' => $query->orderBy($lowestPrice),
            'price_high' => $query->orderByDesc($lowestPrice),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::active()->sorted()->withCount('products')->get();

        if ($request->ajax()) {
            $showing = app()->getLocale() == 'bn'
                ? "{$products->total()} টি পণ্যের মধ্যে 1–".(int) $products->lastItem().' টি দেখানো হচ্ছে'
                : 'Showing 1–'.(int) $products->lastItem()." of {$products->total()} products";

            return response()->json([
                'html' => view('shop._products', compact('products'))->render(),
                'hasMore' => $products->hasMorePages(),
                'showing' => $showing,
            ]);
        }

        return view('shop.index', compact('products', 'categories', 'sort'));
    }
}
