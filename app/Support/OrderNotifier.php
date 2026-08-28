<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * One place that decides who hears about an order.
 *
 * Every dispatch is guarded: a broken mail server or SMS gateway must never
 * cost the shop an order, so failures are logged and swallowed.
 */
class OrderNotifier
{
    public function placed(Order $order): void
    {
        $this->quietly(function () use ($order) {
            $this->customerFor($order)->notify(new OrderPlaced($order));
        }, 'order placed (customer)');

        $this->quietly(function () use ($order) {
            $admins = User::where('role', 'admin')->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewOrderReceived($order));
            }
        }, 'order placed (admin)');
    }

    public function statusChanged(Order $order, string $previousStatus): void
    {
        if ($order->status === $previousStatus || ! OrderStatusChanged::isWorthSending($order->status)) {
            return;
        }

        $this->quietly(function () use ($order, $previousStatus) {
            $this->customerFor($order)->notify(new OrderStatusChanged($order, $previousStatus));
        }, 'order status changed');
    }

    /**
     * Guest checkout is the norm, so route by the details on the order itself
     * rather than requiring a user account.
     */
    private function customerFor(Order $order)
    {
        return Notification::route('mail', $order->user?->email)
            ->route('sms', $order->customer_phone);
    }

    private function quietly(callable $callback, string $context): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning("Notification failed: {$context}", ['error' => $e->getMessage()]);
        }
    }
}
