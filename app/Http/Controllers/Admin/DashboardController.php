<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total'),
            'stock_value' => ProductVariant::selectRaw('SUM(stock * cost_price) as total')->value('total') ?? 0,
            'low_stock_count' => ProductVariant::where('stock', '<', 5)->count(),
            'total_expenses' => Expense::sum('amount'),
            'recent_orders' => Order::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
