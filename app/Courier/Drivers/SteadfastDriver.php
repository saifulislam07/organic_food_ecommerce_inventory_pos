<?php

namespace App\Courier\Drivers;

use App\Courier\Consignment;
use App\Courier\TrackedStatus;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Steadfast Courier (portal.packzy.com).
 *
 * Key and secret go in headers; everything else is JSON. The base URL is a
 * setting rather than a constant for the same reason the SMS gateway's endpoint
 * is: providers in this market move hosts and rename paths between merchant
 * accounts, and that should cost a settings edit rather than a deploy. Check
 * the values against the API document on your own account before going live.
 */
class SteadfastDriver extends Driver
{
    /** Steadfast answers 200 in the body, separately from the HTTP status. */
    private const OK = 200;

    public static function key(): string
    {
        return 'steadfast';
    }

    public static function label(): string
    {
        return 'Steadfast Courier';
    }

    public static function fields(): array
    {
        return [
            'api_key' => [
                'label' => 'API key',
                'type' => 'secret',
                'help' => 'From your Steadfast merchant panel, under API.',
            ],
            'secret_key' => [
                'label' => 'Secret key',
                'type' => 'secret',
            ],
            'base_url' => [
                'label' => 'Base URL',
                'type' => 'url',
                'required' => false,
                'placeholder' => 'https://portal.packzy.com/api/v1',
                'help' => 'Leave blank for the default.',
            ],
        ];
    }

    public function book(Order $order): Consignment
    {
        $response = $this->attempt(fn () => $this->request()->post($this->url('create_order'), [
            'invoice' => $order->order_number,
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->customer_phone,
            'recipient_address' => $order->customer_address,
            'cod_amount' => $this->codAmount($order),
            'note' => $this->parcelNote($order),
        ]));

        if ($response === null) {
            return Consignment::failure($this->unreachable());
        }

        $body = $response->json() ?? [];

        if ($response->failed() || (int) ($body['status'] ?? 0) !== self::OK) {
            return Consignment::failure($this->reason($body, $response->status()), $body);
        }

        $consignment = $body['consignment'] ?? [];

        return Consignment::success(
            consignmentId: isset($consignment['consignment_id']) ? (string) $consignment['consignment_id'] : null,
            trackingCode: isset($consignment['tracking_code']) ? (string) $consignment['tracking_code'] : null,
            status: $consignment['status'] ?? null,
            raw: $body,
        );
    }

    public function track(Order $order): TrackedStatus
    {
        // Three ways in, and they are not equally reliable: the consignment id
        // is Steadfast's own and always works, the tracking code only exists
        // once the parcel is scanned, and the invoice lookup is the fallback for
        // a parcel booked outside this system.
        $path = match (true) {
            filled($order->courier_consignment_id) => 'status_by_cid/'.$order->courier_consignment_id,
            filled($order->courier_tracking_code) => 'status_by_trackingcode/'.$order->courier_tracking_code,
            default => 'status_by_invoice/'.$order->order_number,
        };

        $response = $this->attempt(fn () => $this->request()->get($this->url($path)));

        if ($response === null) {
            return TrackedStatus::failure($this->unreachable());
        }

        $body = $response->json() ?? [];

        if ($response->failed() || (int) ($body['status'] ?? 0) !== self::OK) {
            return TrackedStatus::failure($this->reason($body, $response->status()), $body);
        }

        $raw = (string) ($body['delivery_status'] ?? '');

        if ($raw === '') {
            return TrackedStatus::failure('Steadfast returned no delivery status.', $body);
        }

        return TrackedStatus::found(
            status: $raw,
            orderStatus: $this->mapStatus($raw),
            note: $body['note'] ?? null,
            raw: $body,
        );
    }

    /**
     * Steadfast's vocabulary.
     *
     * The "_approval_pending" family is the important subtlety: the rider has
     * reported an outcome but Steadfast has not confirmed it, and the money has
     * certainly not been remitted. Treating those as delivered would book
     * revenue the shop has not been paid, so they stay at shipped until the
     * plain word arrives.
     */
    protected function statusMap(): array
    {
        return [
            'pending' => 'processing',
            'in_review' => 'processing',
            'hold' => 'shipped',
            'delivered_approval_pending' => 'shipped',
            'partial_delivered_approval_pending' => 'shipped',
            'cancelled_approval_pending' => 'shipped',
            'unknown_approval_pending' => 'shipped',
            'delivered' => 'delivered',
            'partial_delivered' => 'delivered',
            'cancelled' => 'cancelled',
        ];
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'Api-Key' => $this->credential('api_key'),
            'Secret-Key' => $this->credential('secret_key'),
            'Content-Type' => 'application/json',
        ])->acceptJson()->timeout(20);
    }

    private function url(string $path): string
    {
        $base = rtrim($this->credential('base_url', (string) config('courier.drivers.steadfast.base_url')), '/');

        return $base.'/'.ltrim($path, '/');
    }

    private function reason(array $body, int $httpStatus): string
    {
        // Laravel's validation-style bag comes back on a bad address or a
        // duplicate invoice, and it is the only place the real reason appears.
        if (! empty($body['errors']) && is_array($body['errors'])) {
            $first = reset($body['errors']);

            return 'Steadfast rejected the parcel: '.(is_array($first) ? reset($first) : $first);
        }

        return $body['message'] ?? "Steadfast returned HTTP {$httpStatus}.";
    }
}
