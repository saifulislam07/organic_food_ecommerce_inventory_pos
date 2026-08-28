<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;

class DashboardController extends Controller
{
    /** Anything below this is called low stock across the panel. */
    private const LOW_STOCK = 5;

    public function index()
    {
        $orders = $this->orderTotals();
        $stock = $this->stockTotals();

        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_orders' => (int) $orders->total,
            'pending_orders' => (int) $orders->pending,
            'total_revenue' => (float) $orders->revenue,
            'stock_value' => (float) $stock->value,
            'low_stock_count' => (int) $stock->low,
            'total_expenses' => Expense::sum('amount'),
            'recent_orders' => Order::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /** Three figures off one pass over the orders table rather than three. */
    private function orderTotals(): object
    {
        return Order::selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(status = ?) as pending', ['pending'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN total ELSE 0 END) as revenue', ['delivered'])
            ->first();
    }

    private function stockTotals(): object
    {
        return ProductVariant::selectRaw('SUM(stock * cost_price) as value')
            ->selectRaw('SUM(stock < ?) as low', [self::LOW_STOCK])
            ->first();
    }
}
