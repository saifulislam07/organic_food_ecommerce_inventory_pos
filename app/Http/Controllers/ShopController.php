<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::active()->with('variants', 'category');

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
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

        $sort = $request->get('sort', 'latest');
        $query = match ($sort) {
            'name_asc' => $query->orderBy('name_en'),
            'name_desc' => $query->orderBy('name_en', 'desc'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::active()->sorted()->withCount('products')->get();

        if ($request->ajax()) {
            $showing = app()->getLocale() == 'bn'
                ? "{$products->total()} টি পণ্যের মধ্যে 1–" . (int)$products->lastItem() . " টি দেখানো হচ্ছে"
                : "Showing 1–" . (int)$products->lastItem() . " of {$products->total()} products";

            return response()->json([
                'html' => view('shop._products', compact('products'))->render(),
                'hasMore' => $products->hasMorePages(),
                'showing' => $showing
            ]);
        }

        return view('shop.index', compact('products', 'categories', 'sort'));
    }
}
