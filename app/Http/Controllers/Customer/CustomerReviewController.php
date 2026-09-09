<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Review;
use Illuminate\Http\Request;

class CustomerReviewController extends Controller
{
    /**
     * A customer reviewing one item from one of their own delivered orders.
     *
     * Ownership and delivery status are both re-checked here rather than
     * trusted from the page that linked here — the order item id is the only
     * thing the request actually proves.
     */
    public function store(Request $request, OrderItem $orderItem)
    {
        $orderItem->loadMissing('order');

        abort_unless($orderItem->order && $orderItem->order->user_id === auth()->id(), 403);
        abort_unless($orderItem->order->status === 'delivered', 422, 'This order is not delivered yet.');
        abort_if($orderItem->product_id === null, 422, 'This product is no longer available to review.');

        $alreadyReviewed = Review::where('order_id', $orderItem->order_id)
            ->where('product_id', $orderItem->product_id)
            ->where('user_id', auth()->id())
            ->exists();

        abort_if($alreadyReviewed, 422, 'You have already reviewed this product.');

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        Review::create([
            'product_id' => $orderItem->product_id,
            'user_id' => auth()->id(),
            'order_id' => $orderItem->order_id,
            'customer_name' => auth()->user()->name,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'is_approved' => false,
        ]);

        return back()->with('success', 'Thanks! Your review will appear once an admin approves it.');
    }
}
