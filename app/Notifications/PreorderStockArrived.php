<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Stock landed for something people are already waiting on.
 *
 * The whole point of taking a pre-order is that somebody comes back to it, and
 * nothing else in the panel would say the waiting is over — the order sits at
 * "pending" looking exactly like every other pending order.
 */
class PreorderStockArrived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly ProductVariant $variant,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'preorder_stock_arrived',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->customer_name,
            'variant_id' => $this->variant->id,
            'product_name' => $this->variant->product->name ?? 'Product',
            'variant_name' => $this->variant->name,
        ];
    }
}
