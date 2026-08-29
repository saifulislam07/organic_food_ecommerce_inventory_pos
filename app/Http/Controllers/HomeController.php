<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::active()->sorted()->get();
        $bestSellers = Product::active()->bestseller()->withCardData()->take(8)->get();
        $featured = Product::active()->featured()->withCardData()->take(8)->get();
        $trending = Product::active()->trending()->withCardData()->take(4)->get();

        // Bundles carry none of the featured / bestseller flags, so without a
        // section of their own they never reach the front page at all.
        $combos = Product::active()->combo()->withCardData()->latest()->take(4)->get();

        return view('home', compact('categories', 'bestSellers', 'featured', 'trending', 'combos'));
    }
}
