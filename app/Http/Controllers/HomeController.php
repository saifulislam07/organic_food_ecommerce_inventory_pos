<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::active()->sorted()->get();
        $bestSellers = Product::active()->bestseller()->with('variants', 'category')->take(8)->get();
        $featured = Product::active()->featured()->with('variants', 'category')->take(8)->get();
        $trending = Product::active()->trending()->with('variants', 'category')->take(4)->get();

        return view('home', compact('categories', 'bestSellers', 'featured', 'trending'));
    }
}
