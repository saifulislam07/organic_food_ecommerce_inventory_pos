<?php

namespace App\Courier\Drivers;

use App\Courier\Consignment;
use App\Courier\TrackedStatus;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Pathao Courier (Aladdin merchant API).
 *
 * The awkward one. It is OAuth rather than a static key, so every call needs a
 * token first, and it addresses destinations by numeric city and zone ids
 * rather than by the address text. Those ids are asked for once in settings —
 * a shop that ships from and to the same few places is the normal case, and
 * making the shop owner map every Bangladeshi thana before their first parcel
 * would be worse than a sensible default they can override per order later.
 *
 * Verify the base URL and the ids against your own merchant panel: the sandbox
 * and production hosts differ, and so do the zone ids between them.
 */
class PathaoDriver extends Driver
{
    /** Tokens last an hour; refreshed a little early so a call never races the expiry. */
    private const TOKEN_TTL = 3300;

    public static function key(): string
    {
        return 'pathao';
    }

    public static function label(): string
    {
        return 'Pathao Courier';
    }

    public static function fields(): array
    {
        return [
            'client_id' => ['label' => 'Client ID', 'type' => 'secret'],
            'client_secret' => ['label' => 'Client secret', 'type' => 'secret'],
            'username' => ['label' => 'Merchant email', 'help' => 'The login for your Pathao merchant account.'],
            'password' => ['label' => 'Merchant password', 'type' => 'secret'],
            'store_id' => ['label' => 'Store ID', 'help' => 'From Pathao → Stores. A number.'],
            'city_id' => [
                'label' => 'Default city ID',
                'required' => false,
                'help' => 'Used when an order does not say. Dhaka is 1 on most accounts.',
            ],
            'zone_id' => [
                'label' => 'Default zone ID',
                'required' => false,
                'help' => 'Pathao will not accept a parcel without a zone.',
            ],
            'base_url' => [
                'label' => 'Base URL',
                'type' => 'url',
                'required' => false,
                'placeholder' => 'https://api-hermes.pathao.com',
                'help' => 'Use the sandbox host while testing.',
            ],
        ];
    }

    public function book(Order $order): Consignment
    {
        $token = $this->token();

        if ($token === null) {
            return Consignment::failure($this->transportError ?? 'Pathao would not issue a token — check the credentials.');
        }

        $response = $this->attempt(fn () => $this->request($token)->post($this->url('aladdin/api/v1/orders'), [
            'store_id' => (int) $this->credential('store_id'),
            'merchant_order_id' => $order->order_number,
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->customer_phone,
            'recipient_address' => $order->customer_address,
            'recipient_city' => (int) $this->credential('city_id', '0') ?: null,
            'recipient_zone' => (int) $this->credential('zone_id', '0') ?: null,
            // 48 is Pathao's code for a normal delivery; 12 is same-day.
            'delivery_type' => 48,
            // 2 is a parcel, as opposed to a document.
            'item_type' => 2,
            'item_quantity' => $this->itemCount($order),
            'item_weight' => 0.5,
            'amount_to_collect' => $this->codAmount($order),
            'item_description' => $this->parcelNote($order),
        ]));

        if ($response === null) {
            return Consignment::failure($this->unreachable());
        }

        $body = $response->json() ?? [];

        if ($response->failed()) {
            return Consignment::failure($this->reason($body, $response->status()), $body);
        }

        $data = $body['data'] ?? [];

        return Consignment::success(
            consignmentId: isset($data['consignment_id']) ? (string) $data['consignment_id'] : null,
            trackingCode: isset($data['consignment_id']) ? (string) $data['consignment_id'] : null,
            status: $data['order_status'] ?? null,
            raw: $body,
        );
    }

    public function track(Order $order): TrackedStatus
    {
        if (blank($order->courier_consignment_id)) {
            return TrackedStatus::failure('This order has no Pathao consignment ID.');
        }

        $token = $this->token();

        if ($token === null) {
            return TrackedStatus::failure($this->transportError ?? 'Pathao would not issue a token.');
        }

        $response = $this->attempt(fn () => $this->request($token)
            ->get($this->url("aladdin/api/v1/orders/{$order->courier_consignment_id}/info")));

        if ($response === null) {
            return TrackedStatus::failure($this->unreachable());
        }

        $body = $response->json() ?? [];

        if ($response->failed()) {
            return TrackedStatus::failure($this->reason($body, $response->status()), $body);
        }

        $data = $body['data'] ?? [];
        $raw = (string) ($data['order_status'] ?? '');

        if ($raw === '') {
            return TrackedStatus::failure('Pathao returned no order status.', $body);
        }

        return TrackedStatus::found(
            status: $raw,
            orderStatus: $this->mapStatus($raw),
            note: $data['order_status_slug'] ?? null,
            raw: $body,
        );
    }

    /**
     * Pathao's vocabulary.
     *
     * "Payment_Invoice" is the one worth explaining: it means the parcel is
     * delivered and Pathao has raised the remittance invoice. It maps to
     * delivered because for the shop the parcel is gone and the money is coming.
     */
    protected function statusMap(): array
    {
        return [
            'Pending' => 'processing',
            'Pickup_Requested' => 'processing',
            'Assigned_for_Pickup' => 'processing',
            'Picked' => 'shipped',
            'Pickup_Failed' => 'processing',
            'Pickup_Cancelled' => 'cancelled',
            'At_the_Sorting_HUB' => 'shipped',
            'In_Transit' => 'shipped',
            'Received_at_Last_Mile_HUB' => 'shipped',
            'Assigned_for_Delivery' => 'shipped',
            'Delivered' => 'delivered',
            'Partial_Delivery' => 'delivered',
            'Payment_Invoice' => 'delivered',
            'Return' => 'cancelled',
            'Delivery_Failed' => 'shipped',
            'On_Hold' => 'shipped',
            'Cancelled' => 'cancelled',
        ];
    }

    /**
     * A bearer token, cached for its lifetime.
     *
     * Cached per credential set rather than globally: swapping the merchant
     * account in settings has to invalidate the token, and it silently would
     * not if the key were fixed.
     */
    private function token(): ?string
    {
        $key = 'courier.pathao.token.'.md5($this->credential('client_id').'|'.$this->credential('username'));

        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = $this->attempt(fn () => Http::acceptJson()->timeout(20)
            ->post($this->url('aladdin/api/v1/issue-token'), [
                'client_id' => $this->credential('client_id'),
                'client_secret' => $this->credential('client_secret'),
                'username' => $this->credential('username'),
                'password' => $this->credential('password'),
                'grant_type' => 'password',
            ]));

        if ($response === null) {
            $this->transportError = $this->unreachable();

            return null;
        }

        $body = $response->json() ?? [];
        $token = $body['access_token'] ?? null;

        if ($response->failed() || ! is_string($token) || $token === '') {
            $this->transportError = $this->reason($body, $response->status());

            return null;
        }

        // Honour the server's expiry when it gives one, but never trust it to
        // outlive our own ceiling.
        $ttl = min((int) ($body['expires_in'] ?? self::TOKEN_TTL), self::TOKEN_TTL);

        Cache::put($key, $token, max(60, $ttl));

        return $token;
    }

    /** Drop the cached token — called when the credentials are re-saved. */
    public function forgetToken(): void
    {
        Cache::forget('courier.pathao.token.'.md5($this->credential('client_id').'|'.$this->credential('username')));
    }

    private function request(string $token): PendingRequest
    {
        return Http::withToken($token)->acceptJson()->timeout(20);
    }

    private function url(string $path): string
    {
        $base = rtrim($this->credential('base_url', (string) config('courier.drivers.pathao.base_url')), '/');

        return $base.'/'.ltrim($path, '/');
    }

    private function reason(array $body, int $httpStatus): string
    {
        if (! empty($body['errors']) && is_array($body['errors'])) {
            $first = reset($body['errors']);

            return 'Pathao rejected the parcel: '.(is_array($first) ? reset($first) : $first);
        }

        return $body['message'] ?? "Pathao returned HTTP {$httpStatus}.";
    }
}
