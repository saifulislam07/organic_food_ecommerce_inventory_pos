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

    /**
     * Served from here rather than public/robots.txt so the Sitemap line
     * carries whatever domain the site is actually being served on — a file
     * on disk would have to hardcode one, and get it wrong on staging.
     *
     * Note what is *not* here: the shop's "hidden from search" setting stays
     * out of this file on purpose. Disallow would stop Google fetching the
     * pages at all, and a page it cannot fetch is a page whose noindex tag it
     * never reads — the URL can end up indexed anyway. The meta robots tag in
     * the layout is the one that does that job.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Private or single-use pages: nothing to rank, and crawling them
            // only spends the budget meant for products.
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /customer',
            'Disallow: /profile',
            'Disallow: /order-success',
            'Disallow: /login',
            'Disallow: /register',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
