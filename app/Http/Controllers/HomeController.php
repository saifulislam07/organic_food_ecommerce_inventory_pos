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

        return view('home', compact('categories', 'bestSellers', 'featured', 'trending'));
    }
}
