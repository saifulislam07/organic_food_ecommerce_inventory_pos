<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\Review;
use App\Models\SiteBlock;

class HomeController extends Controller
{
    public function index()
    {
        // A shop that has not built a slider yet still gets a hero: the single
        // panel the settings describe, rendered through the same carousel.
        $slides = HeroSlide::active()->sorted()->get();

        if ($slides->isEmpty()) {
            $slides = collect([HeroSlide::fallback()]);
        }

        // The counts are for the rail beside the hero. The layout's own copy of
        // this list comes from a view composer, which does not reach a child
        // view — so the front page reads its categories from here.
        $categories = Category::active()->sorted()->withCount('products')->get();

        // Ten and five fill the front page's five-across grid without leaving a
        // ragged last row on a wide screen.
        $bestSellers = Product::active()->bestseller()->withCardData()->take(10)->get();
        $featured = Product::active()->featured()->withCardData()->take(10)->get();
        $trending = Product::active()->trending()->withCardData()->take(5)->get();

        // Bundles carry none of the featured / bestseller flags, so without a
        // section of their own they never reach the front page at all.
        $combos = Product::active()->combo()->withCardData()->latest()->take(5)->get();

        // Admin > Settings > Storefront Blocks. Both come out of one query the
        // layout has already made for its own lists.
        $services = SiteBlock::list('service');
        $promos = SiteBlock::list('promo');

        // Approved reviews only — a customer's submission or an admin's own
        // entry both sit hidden until then.
        $reviews = Review::approved()->with('product')->latest()->take(12)->get();

        return view('home', compact(
            'slides', 'categories', 'bestSellers', 'featured', 'trending', 'combos', 'services', 'promos', 'reviews'
        ));
    }
}
