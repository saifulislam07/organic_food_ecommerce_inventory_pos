<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    /**
     * The order the policy pages read in, rather than alphabetically: what the
     * shop is, then what it will do, then what it asks of you. Anything the
     * admin adds later falls in after these, by title.
     */
    private const READING_ORDER = [
        'about-us',
        'faq',
        'shipping-policy',
        'return-policy',
        'terms-and-conditions',
        'privacy-policy',
    ];

    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Somebody reading the return window usually wants the delivery times
        // next, so the whole set is offered alongside rather than sending them
        // back to the footer to find the sibling page.
        $related = Page::where('is_active', true)
            ->get(['id', 'slug', 'title_en', 'title_bn', 'updated_at'])
            ->sortBy([
                fn (Page $a, Page $b) => $this->rank($a) <=> $this->rank($b),
                fn (Page $a, Page $b) => $a->title <=> $b->title,
            ])
            ->values();

        return view('pages.show', compact('page', 'related'));
    }

    private function rank(Page $page): int
    {
        $position = array_search($page->slug, self::READING_ORDER, true);

        return $position === false ? count(self::READING_ORDER) : $position;
    }
}
