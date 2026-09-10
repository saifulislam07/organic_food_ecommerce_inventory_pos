<?php

namespace App\Courier\Drivers;

use App\Courier\Contracts\CourierDriver;
use App\Models\Order;
use Illuminate\Http\Client\Response;

/**
 * The part every courier driver does the same way: hold its credentials, say
 * whether it has enough of them to be used, and turn a courier's own status
 * word into one of ours.
 */
abstract class Driver implements CourierDriver
{
    /**
     * Why the last call never reached the courier at all, as opposed to
     * reaching it and being refused. Set by attempt().
     */
    protected ?string $transportError = null;

    /** @param  array<string, string|null>  $credentials  keyed by the driver's own field names */
    public function __construct(protected readonly array $credentials = []) {}

    /**
     * The courier's vocabulary translated into our order statuses.
     *
     * A word that is not in the map returns null from mapStatus(), and null
     * means "leave the order where it is" — see TrackedStatus.
     *
     * @return array<string, string>
     */
    abstract protected function statusMap(): array;

    public function isConfigured(): bool
    {
        foreach (static::fields() as $key => $field) {
            if (($field['required'] ?? true) && blank($this->credentials[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    protected function credential(string $key, string $default = ''): string
    {
        $value = $this->credentials[$key] ?? null;

        return filled($value) ? trim((string) $value) : $default;
    }

    /**
     * Our status for a courier's word, or null when we do not recognise it.
     *
     * Matched case-insensitively and with separators flattened, because the
     * same provider will return "In Review", "in_review" and "in-review" from
     * different endpoints of the same API.
     */
    protected function mapStatus(?string $raw): ?string
    {
        if (blank($raw)) {
            return null;
        }

        $needle = preg_replace('/[^a-z0-9]+/', '_', mb_strtolower(trim($raw)));

        foreach ($this->statusMap() as $word => $status) {
            if (preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($word)) === $needle) {
                return $status;
            }
        }

        return null;
    }

    /**
     * What the courier still has to collect from the customer.
     *
     * Anything already paid — a bKash transfer before dispatch, a part payment
     * at the counter — must not be collected twice, so it comes off here rather
     * than sending the courier the order total and sorting it out later.
     */
    protected function codAmount(Order $order): float
    {
        return $order->amount_due;
    }

    /** A short parcel description, since most providers insist on one. */
    protected function parcelNote(Order $order): string
    {
        $note = trim((string) $order->notes);

        return $note !== '' ? mb_substr($note, 0, 240) : 'Order '.$order->order_number;
    }

    protected function itemCount(Order $order): int
    {
        return max(1, (int) $order->items->sum('quantity'));
    }

    /**
     * Make one HTTP call, turning "could not reach them" into a null.
     *
     * A courier having a bad afternoon must never surface as a stack trace on
     * the order screen, and it must stay distinguishable from a courier that
     * answered and said no — hence the null rather than a failed Response.
     */
    protected function attempt(callable $call): ?Response
    {
        $this->transportError = null;

        try {
            return $call();
        } catch (\Throwable $e) {
            $this->transportError = $e->getMessage();

            return null;
        }
    }

    /** The message for a call that never got through. */
    protected function unreachable(): string
    {
        $reason = $this->transportError ? ': '.$this->transportError : '.';

        return 'Could not reach '.static::label().$reason;
    }
}
