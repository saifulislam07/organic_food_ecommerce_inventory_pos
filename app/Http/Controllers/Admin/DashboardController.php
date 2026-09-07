<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardSummary;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The panel's front page.
     *
     * Sections are gathered only for a viewer allowed to see them. That is not
     * only about hiding a card: the profit block alone is seven queries, and a
     * packer who may not read it should not pay for it either.
     *
     * The permission each section needs is the one that opens the screen it
     * summarises, so nothing here can show a number the viewer could not have
     * reached by clicking through.
     */
    public function index(Request $request, DashboardSummary $summary)
    {
        $user = $request->user();

        $can = fn (string $permission) => (bool) $user?->can($permission);

        return view('admin.dashboard', [
            'monthLabel' => $summary->monthLabel(),

            // Trading. Anyone who can reach the panel is here to sell.
            'sales' => $summary->sales(),
            'attention' => $summary->attention(),
            'pipeline' => $can('orders.view') ? $summary->pipeline() : null,
            'channels' => $can('orders.view') ? $summary->channels() : null,
            'recentOrders' => $can('orders.view') ? $summary->recentOrders() : null,

            // Stock.
            'standing' => $summary->standing(),
            'lowStockItems' => $can('inventory.view') ? $summary->lowStockItems() : null,
            'catalogue' => $summary->catalogue(),
            'topProducts' => $can('reports.view') ? $summary->topProducts() : null,

            // Money. The owner's numbers, behind the owner's permissions.
            'money' => $can('reports.view') ? $summary->money() : null,
            'capital' => $can('investors.view') ? $summary->capital() : null,
            'recentExpenses' => $can('expenses.view') ? $summary->recentExpenses() : null,

            'campaigns' => $can('landing-pages.view') ? $summary->campaigns() : null,
        ]);
    }
}
