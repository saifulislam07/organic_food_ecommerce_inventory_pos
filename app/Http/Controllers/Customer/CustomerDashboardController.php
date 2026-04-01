<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $orders = auth()->user()->orders()->latest()->paginate(10);
        return view('customer.dashboard', compact('orders'));
    }

    public function show(string $orderNumber)
    {
        $order = auth()->user()->orders()
            ->where('order_number', $orderNumber)
            ->with(['items.product', 'items.variant'])
            ->firstOrFail();

        return view('customer.orders.show', compact('order'));
    }

    public function invoice(string $orderNumber)
    {
        $order = auth()->user()->orders()
            ->where('order_number', $orderNumber)
            ->with(['items.product', 'items.variant'])
            ->firstOrFail();

        return view('admin.orders.invoice', compact('order'));
    }
}
