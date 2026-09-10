<?php

namespace App\Courier;

/**
 * Where the courier says the parcel is.
 *
 * Two statuses, deliberately: `status` is the courier's own word, kept exactly
 * as it arrived, and `orderStatus` is what that means for us. The second is
 * nullable and that is the important part — a courier word nobody has mapped
 * yet must leave the order alone rather than guess, or one unrecognised string
 * silently marks a shop's orders delivered.
 */
class TrackedStatus
{
    private function __construct(
        public readonly bool $found,
        public readonly ?string $status = null,
        public readonly ?string $orderStatus = null,
        public readonly ?string $note = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function found(
        string $status,
        ?string $orderStatus = null,
        ?string $note = null,
        array $raw = [],
    ): self {
        return new self(true, $status, $orderStatus, $note, null, $raw);
    }

    public static function failure(string $error, array $raw = []): self
    {
        return new self(false, null, null, null, $error, $raw);
    }

    /** True when this reading should move the order on. */
    public function movesOrder(?string $currentOrderStatus): bool
    {
        return $this->found
            && $this->orderStatus !== null
            && $this->orderStatus !== $currentOrderStatus;
    }
}
