<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Services\LandingPageOrder;
use App\Support\CampaignTracking;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    /**
     * The page an ad points at.
     *
     * A switched-off page is a draft and 404s for the public, but an admin can
     * still open it to check their work. A page that ran and expired keeps
     * rendering — an ad can outlive its offer, and a dead link looks broken
     * where "মেয়াদ শেষ" at least explains itself.
     */
    public function show(Request $request, string $slug)
    {
        $page = LandingPage::with(['items.product', 'items.variant.comboItems.component'])
            ->where('slug', $slug)
            ->firstOrFail();

        $preview = false;

        if (! $page->is_active) {
            if (! $request->user()?->can('landing-pages.view')) {
                abort(404);
            }

            $preview = true;
        }

        // A preview is the admin looking at their own page; counting it would
        // spoil the conversion rate they are about to read.
        if (! $preview) {
            LandingPage::whereKey($page->getKey())->increment('views');
        }

        $service = app(LandingPageOrder::class);

        return view('landing.show', [
            'page' => $page,
            'preview' => $preview,
            'open' => $page->isRunning() || $preview,
            'closedReason' => $page->closedReason(),
            'tracking' => CampaignTracking::capture($request),
            // What the page quotes before anyone touches the form: the default
            // selection, delivered inside Dhaka.
            'openingQuote' => $service->quote(
                $page,
                $service->lines($page, []),
                'dhaka_inside'
            ),
        ]);
    }
}
