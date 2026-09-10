<?php

namespace App\Courier\Drivers;

use App\Courier\Consignment;
use App\Courier\TrackedStatus;
use App\Models\Order;

/**
 * A courier with no API — a local rider, Sundarban, whoever the shop rang up.
 *
 * It exists so the courier panel is not a dead end for the couriers most small
 * shops actually use. Booking records the consignment number the shop typed in;
 * tracking says nothing, because there is nothing to ask.
 */
class ManualCourierDriver extends Driver
{
    public static function key(): string
    {
        return 'manual';
    }

    public static function label(): string
    {
        return 'Manual / other courier';
    }

    public static function fields(): array
    {
        return [
            'display_name' => [
                'label' => 'Courier name',
                'help' => 'Shown on the order — Sundarban, SA Paribahan, your own rider.',
                'required' => false,
                'placeholder' => 'Local rider',
            ],
            'tracking_url' => [
                'label' => 'Tracking page',
                'type' => 'url',
                'help' => 'Optional. Use {code} where the consignment number goes.',
                'required' => false,
                'placeholder' => 'https://example.com/track?id={code}',
            ],
        ];
    }

    /** Nothing is required, so this one is always ready to use. */
    public function isConfigured(): bool
    {
        return true;
    }

    public function book(Order $order): Consignment
    {
        // Whatever the shop typed into the courier panel is already on the
        // order; this driver only confirms it rather than calling anyone.
        return Consignment::success(
            $order->courier_consignment_id,
            $order->courier_tracking_code,
            'booked',
        );
    }

    public function track(Order $order): TrackedStatus
    {
        return TrackedStatus::failure(
            'This courier has no API — update the status by hand.'
        );
    }

    protected function statusMap(): array
    {
        return [];
    }
}
