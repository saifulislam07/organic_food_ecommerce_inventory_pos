<?php

namespace App\Services;

use App\Courier\Consignment;
use App\Courier\CourierManager;
use App\Courier\TrackedStatus;
use App\Models\Order;
use App\Support\CourierSettings;
use Illuminate\Support\Facades\Log;

/**
 * Handing an order to a courier, and asking that courier where it got to.
 *
 * The point of the second half is that the shop stops typing statuses in by
 * hand: the courier already knows whether the parcel arrived, and it is the
 * only party that knows. Every reading is written back to the order — even one
 * that changes nothing — so "when did we last hear anything about this parcel"
 * always has an answer.
 */
class CourierDispatch
{
    public function __construct(
        private readonly CourierManager $couriers,
        private readonly OrderWorkflow $workflow,
    ) {}

    /**
     * Book a parcel and remember what the courier called it.
     *
     * @param  string|null  $courier  driver key, or null for the shop's default
     * @param  array{consignment_id?: string|null, tracking_code?: string|null}  $manual
     *                                                                                    ids typed in by hand, for a courier with no API
     */
    public function book(Order $order, ?string $courier = null, array $manual = []): Consignment
    {
        $key = $courier ?: CourierSettings::default();
        $driver = $this->couriers->driver($key);

        if ($driver === null) {
            return Consignment::failure('No courier is set up. Add one under Settings → Couriers.');
        }

        if (! $driver->isConfigured()) {
            return Consignment::failure($driver::label().' is missing some of its credentials.');
        }

        // What to put back if the courier says no. Kept before anything is
        // written, because the write has to happen first: a manual courier only
        // echoes back what it was given, so the ids have to be on the order
        // before it is asked.
        $before = $order->only(['courier', 'courier_consignment_id', 'courier_tracking_code']);

        $order->forceFill([
            'courier' => $key,
            'courier_consignment_id' => $manual['consignment_id'] ?? $order->courier_consignment_id,
            'courier_tracking_code' => $manual['tracking_code'] ?? $order->courier_tracking_code,
        ]);

        $order->loadMissing('items');

        $result = $driver->book($order);

        if (! $result->booked) {
            // An order nobody accepted must not be left looking as though it is
            // with a courier. If the attempt stuck, the panel would offer to
            // track a parcel that does not exist and hide the button that lets
            // anyone try again — so the attempt is rolled back and only the
            // reason is kept.
            $order->forceFill($before + ['courier_status' => null, 'courier_status_note' => $result->error])->save();

            Log::warning('Courier booking failed', [
                'order' => $order->order_number,
                'courier' => $key,
                'error' => $result->error,
            ]);

            return $result;
        }

        $order->forceFill([
            'courier_consignment_id' => $result->consignmentId ?: $order->courier_consignment_id,
            'courier_tracking_code' => $result->trackingCode ?: $order->courier_tracking_code,
            'courier_status' => $result->status,
            'courier_status_note' => null,
            'courier_synced_at' => now(),
        ])->save();

        // Handed over, but not yet moving: the courier has accepted the booking,
        // the rider has not been. Its own status will carry the order to shipped
        // once the parcel is actually collected.
        if (in_array($order->status, ['pending', 'confirmed'], true)) {
            $this->workflow->changeStatus($order, 'processing');
        }

        return $result;
    }

    /**
     * Ask the courier where this parcel is and move the order if it says so.
     *
     * A status word nobody has mapped, or a courier that cannot be reached, is
     * recorded and otherwise ignored — see TrackedStatus for why guessing is
     * the one thing this must never do.
     */
    public function sync(Order $order): TrackedStatus
    {
        if (blank($order->courier)) {
            return TrackedStatus::failure('This order has not been sent to a courier.');
        }

        $driver = $this->couriers->driver($order->courier);

        if ($driver === null) {
            return TrackedStatus::failure('The courier this order was booked with is no longer set up.');
        }

        if (! $driver->isConfigured()) {
            return TrackedStatus::failure($driver::label().' is missing some of its credentials.');
        }

        $result = $driver->track($order);

        $order->courier_synced_at = now();

        if (! $result->found) {
            $order->courier_status_note = $result->error;
            $order->save();

            return $result;
        }

        $order->courier_status = $result->status;
        $order->courier_status_note = $result->note;
        $order->save();

        if ($result->movesOrder($order->status)) {
            $this->workflow->changeStatus($order, $result->orderStatus);
        }

        return $result;
    }

    /**
     * Sync every parcel still in flight.
     *
     * @return array{checked: int, moved: int, failed: int}
     */
    public function syncAll(?string $courier = null): array
    {
        $tally = ['checked' => 0, 'moved' => 0, 'failed' => 0];

        Order::query()
            ->whereNotNull('courier')
            ->whereIn('status', (array) config('courier.sync.statuses', ['confirmed', 'processing', 'shipped']))
            ->when($courier, fn ($query) => $query->where('courier', $courier))
            ->orderBy('id')
            ->limit((int) config('courier.sync.batch', 100))
            ->each(function (Order $order) use (&$tally) {
                $before = $order->status;
                $result = $this->sync($order);

                $tally['checked']++;

                if (! $result->found) {
                    $tally['failed']++;
                } elseif ($order->status !== $before) {
                    $tally['moved']++;
                }
            });

        return $tally;
    }
}
