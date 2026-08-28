<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        return response()
            ->view('sitemap', [
                'products' => Product::active()->get(),
                'categories' => Category::active()->get(),
                // CMS pages were missing entirely, so nothing linked them for Google.
                'pages' => Page::where('is_active', true)->get(),
            ])
            ->header('Content-Type', 'text/xml');
    }
}
