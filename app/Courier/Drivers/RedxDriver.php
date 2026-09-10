<?php

namespace App\Courier\Drivers;

use App\Courier\Consignment;
use App\Courier\TrackedStatus;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * RedX (openapi.redx.com.bd).
 *
 * A single access token, and a tracking id rather than a consignment id. Its
 * tracking endpoint answers with a history rather than a status field, so the
 * current state is the newest entry — see track().
 *
 * Check the base URL and your delivery area id against the merchant panel
 * before going live; RedX refuses a parcel whose area it does not recognise.
 */
class RedxDriver extends Driver
{
    public static function key(): string
    {
        return 'redx';
    }

    public static function label(): string
    {
        return 'RedX';
    }

    public static function fields(): array
    {
        return [
            'access_token' => [
                'label' => 'API access token',
                'type' => 'secret',
                'help' => 'From the RedX merchant panel, under Developer / API.',
            ],
            'pickup_store_id' => [
                'label' => 'Pickup store ID',
                'help' => 'The RedX store your parcels are collected from.',
            ],
            'area_id' => [
                'label' => 'Default delivery area ID',
                'required' => false,
                'help' => 'Used when an order does not name one.',
            ],
            'base_url' => [
                'label' => 'Base URL',
                'type' => 'url',
                'required' => false,
                'placeholder' => 'https://openapi.redx.com.bd/v1.0.0-beta',
                'help' => 'Leave blank for the default.',
            ],
        ];
    }

    public function book(Order $order): Consignment
    {
        $response = $this->attempt(fn () => $this->request()->post($this->url('parcel'), [
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'delivery_area_id' => (int) $this->credential('area_id', '0') ?: null,
            'merchant_invoice_id' => $order->order_number,
            'cash_collection_amount' => (string) $this->codAmount($order),
            // What the parcel is worth if it is lost, which is not what the
            // customer still owes — a prepaid order collects nothing but is
            // still worth insuring for its full value.
            'value' => (string) round((float) $order->total, 2),
            'parcel_weight' => 500,
            'pickup_store_id' => (int) $this->credential('pickup_store_id'),
            'instruction' => $this->parcelNote($order),
            'parcel_details_json' => $order->items->map(fn ($item) => [
                'name' => $item->product_name,
                'category' => 'general',
                'value' => (float) $item->unit_price,
            ])->values()->all(),
        ]));

        if ($response === null) {
            return Consignment::failure($this->unreachable());
        }

        $body = $response->json() ?? [];

        if ($response->failed()) {
            return Consignment::failure($this->reason($body, $response->status()), $body);
        }

        $tracking = $body['tracking_id'] ?? ($body['data']['tracking_id'] ?? null);

        if (blank($tracking)) {
            return Consignment::failure('RedX accepted the call but returned no tracking ID.', $body);
        }

        return Consignment::success(
            consignmentId: (string) $tracking,
            trackingCode: (string) $tracking,
            status: 'pickup-pending',
            raw: $body,
        );
    }

    public function track(Order $order): TrackedStatus
    {
        $tracking = $order->courier_tracking_code ?: $order->courier_consignment_id;

        if (blank($tracking)) {
            return TrackedStatus::failure('This order has no RedX tracking ID.');
        }

        $response = $this->attempt(fn () => $this->request()->get($this->url('parcel/track/'.$tracking)));

        if ($response === null) {
            return TrackedStatus::failure($this->unreachable());
        }

        $body = $response->json() ?? [];

        if ($response->failed()) {
            return TrackedStatus::failure($this->reason($body, $response->status()), $body);
        }

        // RedX hands back the whole journey, newest first on every account seen
        // so far — but "so far" is not a guarantee, so the newest entry is
        // picked by its timestamp rather than by its position.
        $history = $body['tracking'] ?? ($body['data']['tracking'] ?? []);

        if (! is_array($history) || $history === []) {
            return TrackedStatus::failure('RedX has no tracking history for this parcel yet.', $body);
        }

        $latest = collect($history)
            ->sortBy(fn ($entry) => strtotime((string) ($entry['time'] ?? '')) ?: 0)
            ->last();

        $raw = (string) ($latest['status'] ?? '');

        if ($raw === '') {
            return TrackedStatus::failure('RedX returned a history with no status on it.', $body);
        }

        return TrackedStatus::found(
            status: $raw,
            orderStatus: $this->mapStatus($raw),
            note: $latest['message_en'] ?? null,
            raw: $body,
        );
    }

    protected function statusMap(): array
    {
        return [
            'pickup-pending' => 'processing',
            'pickup-assigned' => 'processing',
            'pickup-failed' => 'processing',
            'received-at-pickup-hub' => 'shipped',
            'in-transit' => 'shipped',
            'received-at-delivery-hub' => 'shipped',
            'delivery-assigned' => 'shipped',
            'delivery-failed' => 'shipped',
            'on-hold' => 'shipped',
            'delivered' => 'delivered',
            'partially-delivered' => 'delivered',
            'return-assigned' => 'cancelled',
            'returned' => 'cancelled',
            'cancelled' => 'cancelled',
        ];
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'API-ACCESS-TOKEN' => 'Bearer '.$this->credential('access_token'),
        ])->acceptJson()->timeout(20);
    }

    private function url(string $path): string
    {
        $base = rtrim($this->credential('base_url', (string) config('courier.drivers.redx.base_url')), '/');

        return $base.'/'.ltrim($path, '/');
    }

    private function reason(array $body, int $httpStatus): string
    {
        return $body['message']
            ?? $body['error']
            ?? "RedX returned HTTP {$httpStatus}.";
    }
}
