<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Review;

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

        // Which of this order's products the customer has already reviewed,
        // so the "Write a Review" button can turn into a confirmation instead.
        $reviewedProductIds = Review::where('order_id', $order->id)
            ->where('user_id', auth()->id())
            ->pluck('product_id')
            ->all();

        return view('customer.orders.show', compact('order', 'reviewedProductIds'));
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
