<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\PaymentAccounts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Everything the panel knows, gathered for one screen.
 *
 * Split into sections rather than one big array because the dashboard is not
 * one audience: a packer needs the orders waiting and the stock running out,
 * and has no business seeing what the owners have drawn out. The controller
 * asks for the sections the viewer may see, so a section nobody is allowed to
 * read costs nothing to skip.
 *
 * Every section is one query or a small fixed number. Nothing here loops over a
 * result and asks another question.
 */
class DashboardSummary
{
    /** Anything below this is called low stock across the panel. */
    public const LOW_STOCK = 5;

    /** The order states that count as money earned, matching the P&L report. */
    private const EARNED = ProfitLossReport::EARNED;

    /** Statuses shown in the breakdown, in the order an order moves through. */
    private const PIPELINE = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

    private readonly Carbon $today;

    private readonly Carbon $monthStart;

    public function __construct()
    {
        $this->today = Carbon::now()->startOfDay();
        $this->monthStart = Carbon::now()->startOfMonth();
    }

    public function monthLabel(): string
    {
        return $this->monthStart->format('F Y');
    }

    /* ------------------------------------------------------------ trading */

    /**
     * Orders and money for today, this month, and since the beginning.
     *
     * One pass over the orders table for all nine figures: the ranges are
     * conditional sums rather than three separate queries, because the table
     * only grows and the dashboard is the most-opened page in the panel.
     */
    public function sales(): array
    {
        $earned = "'".implode("','", self::EARNED)."'";

        $row = Order::query()
            ->selectRaw('COUNT(*) as all_orders')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ({$earned}) THEN total ELSE 0 END), 0) as all_revenue")
            ->selectRaw('COALESCE(SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END), 0) as month_orders', [$this->monthStart])
            ->selectRaw("COALESCE(SUM(CASE WHEN created_at >= ? AND status IN ({$earned}) THEN total ELSE 0 END), 0) as month_revenue", [$this->monthStart])
            ->selectRaw('COALESCE(SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END), 0) as today_orders', [$this->today])
            ->selectRaw("COALESCE(SUM(CASE WHEN created_at >= ? AND status IN ({$earned}) THEN total ELSE 0 END), 0) as today_revenue", [$this->today])
            ->first();

        return [
            'today' => ['orders' => (int) $row->today_orders, 'revenue' => (float) $row->today_revenue],
            'month' => ['orders' => (int) $row->month_orders, 'revenue' => (float) $row->month_revenue],
            'all' => ['orders' => (int) $row->all_orders, 'revenue' => (float) $row->all_revenue],
        ];
    }

    /** How many orders sit in each state, every state present at zero. */
    public function pipeline(): array
    {
        $counts = Order::query()
            ->groupBy('status')
            ->pluck(DB::raw('COUNT(*)'), 'status')
            ->all();

        $pipeline = [];

        foreach (self::PIPELINE as $status) {
            $pipeline[$status] = (int) ($counts[$status] ?? 0);
        }

        // A status added by hand still has orders behind it.
        foreach ($counts as $status => $count) {
            $pipeline[$status] ??= (int) $count;
        }

        return $pipeline;
    }

    /** Where this month's orders came from: the website, the counter, an ad. */
    public function channels(): array
    {
        $rows = Order::query()
            ->where('created_at', '>=', $this->monthStart)
            ->groupBy('source')
            ->selectRaw('source, COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->get();

        $channels = [];

        foreach (Order::SOURCES as $key => $label) {
            $row = $rows->firstWhere('source', $key);

            $channels[$key] = [
                'label' => $label,
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => (float) ($row->revenue ?? 0),
            ];
        }

        return $channels;
    }

    /* ---------------------------------------------------------- attention */

    /** The things that want doing today, each with somewhere to go. */
    public function attention(): array
    {
        $orders = Order::query()
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as pending', ['pending'])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as confirmed', ['confirmed'])
            ->first();

        $stock = ProductVariant::query()
            ->selectRaw('COALESCE(SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END), 0) as out_of_stock')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock > 0 AND stock < ? THEN 1 ELSE 0 END), 0) as low', [self::LOW_STOCK])
            ->first();

        return [
            'pending_orders' => (int) $orders->pending,
            'confirmed_orders' => (int) $orders->confirmed,
            'out_of_stock' => (int) $stock->out_of_stock,
            'low_stock' => (int) $stock->low,
        ];
    }

    /** The variants actually running out, so the number has something behind it. */
    public function lowStockItems(int $limit = 8): Collection
    {
        return ProductVariant::with('product:id,name')
            ->where('stock', '<', self::LOW_STOCK)
            ->orderBy('stock')
            ->limit($limit)
            ->get(['id', 'product_id', 'name', 'stock']);
    }

    /* -------------------------------------------------------------- money */

    /**
     * This month's profit and where the cash sits.
     *
     * Handed straight to the report that already knows how to work this out,
     * rather than a second implementation that would drift from it. Investor
     * capital is in the account table and out of the profit figure, which is
     * that report's rule, not this one's.
     */
    public function money(): array
    {
        $report = ProfitLossReport::between(
            $this->monthStart->toDateString(),
            Carbon::now()->toDateString(),
        )->summary();

        $cash = 0.0;

        foreach ($report['accounts'] as $head) {
            $cash += $head['net'];
        }

        return $report + ['cash_net' => $cash];
    }

    /** Assets and lifetime spend — the numbers that do not belong to a month. */
    public function standing(): array
    {
        $stock = ProductVariant::query()
            ->selectRaw('COALESCE(SUM(stock * COALESCE(cost_price, 0)), 0) as value')
            ->selectRaw('COALESCE(SUM(stock * COALESCE(price, 0)), 0) as retail')
            ->first();

        return [
            'stock_cost' => (float) $stock->value,
            'stock_retail' => (float) $stock->retail,
            'expenses_all_time' => (float) Expense::sum('amount'),
        ];
    }

    /** What the owners have put in and taken out, and who is holding what. */
    public function capital(): array
    {
        $invested = (float) Investment::sum('amount');
        $withdrawn = (float) Withdrawal::sum('amount');

        return [
            'invested' => $invested,
            'withdrawn' => $withdrawn,
            'in_business' => $invested - $withdrawn,
            'investors' => Investor::query()
                ->withSum('investments', 'amount')
                ->withSum('withdrawals', 'amount')
                ->sorted()
                ->limit(6)
                ->get(['id', 'name', 'is_active']),
        ];
    }

    /* ---------------------------------------------------------- catalogue */

    /** The size of the shop, in one query each because they are four tables. */
    public function catalogue(): array
    {
        return [
            'products' => Product::count(),
            'categories' => Category::count(),
            'suppliers' => Supplier::count(),
            'customers' => User::where('role', '!=', 'admin')->count(),
        ];
    }

    /** What actually sold this month, by units. */
    public function topProducts(int $limit = 6): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::EARNED)
            ->where('orders.created_at', '>=', $this->monthStart)
            ->groupBy('order_items.product_name')
            ->select('order_items.product_name as name')
            ->selectRaw('SUM(order_items.quantity) as units')
            ->selectRaw('SUM(order_items.total) as revenue')
            ->orderByDesc('units')
            ->limit($limit)
            ->get();
    }

    /* -------------------------------------------------------- ad spending */

    /**
     * How the campaign pages are doing.
     *
     * Views come off the pages themselves; orders and money come off the orders
     * that carry a page id, so a page that has been switched off still reports
     * what it earned while it ran.
     */
    public function campaigns(int $limit = 5): array
    {
        $pages = LandingPage::query()
            ->withCount('orders')
            ->withSum(
                ['orders as revenue' => fn ($q) => $q->where('status', '!=', 'cancelled')],
                'total'
            )
            ->orderByDesc('views')
            ->limit($limit)
            ->get(['id', 'slug', 'internal_name', 'views', 'is_active']);

        $totals = LandingPage::query()
            ->selectRaw('COUNT(*) as pages')
            ->selectRaw('COALESCE(SUM(views), 0) as views')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as live')
            ->first();

        return [
            'pages' => (int) $totals->pages,
            'live' => (int) $totals->live,
            'views' => (int) $totals->views,
            'top' => $pages,
        ];
    }

    /* ------------------------------------------------------------- recent */

    public function recentOrders(int $limit = 8): Collection
    {
        return Order::query()->latest()->limit($limit)->get();
    }

    public function recentExpenses(int $limit = 5): Collection
    {
        return Expense::query()->orderByDesc('expense_date')->orderByDesc('id')->limit($limit)->get();
    }

    /* --------------------------------------------------------------- misc */

    /** The account heads, for the small cash table. */
    public function accountLabel(?string $key): string
    {
        return PaymentAccounts::label($key);
    }
}
