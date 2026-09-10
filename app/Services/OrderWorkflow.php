<?php

namespace App\Services;

use App\Models\Order;
use App\Support\OrderNotifier;

/**
 * The things that happen to an order after it exists.
 *
 * Status used to be changed in the one place that had a dropdown for it. Now
 * three things move an order — a person, a courier's sync, and the settlement
 * form — and every one of them has to stamp the delivery date and tell the
 * customer in the same way. That shared behaviour lives here rather than being
 * copied into each caller and drifting apart.
 */
class OrderWorkflow
{
    public function __construct(private readonly OrderNotifier $notifier) {}

    /**
     * Move an order to a new status, telling the customer if it is worth telling.
     *
     * Returns true when something actually changed, so callers can stay quiet
     * about a sync that found nothing new.
     */
    public function changeStatus(Order $order, string $status): bool
    {
        $previous = $order->status;

        if ($previous === $status) {
            return false;
        }

        $order->status = $status;

        // The date the parcel was handed over is not the date the row was last
        // touched, and a report asking "what did we deliver in August" wants
        // the first. An order corrected back out of delivered loses the stamp,
        // because it did not happen.
        if ($status === 'delivered') {
            $order->delivered_at ??= now();
        } elseif ($previous === 'delivered') {
            $order->delivered_at = null;
        }

        $order->save();

        $this->notifier->statusChanged($order, $previous);

        return true;
    }

    /**
     * Record what a delivery actually settled for, and mark it delivered.
     *
     * This is the moment the books find out what the order was really worth:
     * the courier keeps its fee out of what it collects, so the money that
     * reaches the shop is the total minus that fee, minus anything the customer
     * had already paid. Until this is filled in the profit and loss report has
     * to assume the order settled at its face value, which is the assumption
     * this form exists to replace.
     *
     * @param  array{collected_amount?: float|null, collected_in?: string|null, courier_charge?: float|null, settlement_note?: string|null}  $settlement
     */
    public function settle(Order $order, array $settlement): void
    {
        $order->fill([
            'collected_amount' => $settlement['collected_amount'] ?? null,
            'collected_in' => $settlement['collected_in'] ?? null,
            'courier_charge' => $settlement['courier_charge'] ?? 0,
            'settlement_note' => $settlement['settlement_note'] ?? null,
        ]);

        // Save the figures whether or not the status moves — settling an order
        // that a courier sync already marked delivered must not be a no-op.
        $order->save();

        $this->changeStatus($order, 'delivered');
    }
}
