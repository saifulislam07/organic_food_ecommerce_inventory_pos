<?php

namespace App\Courier;

/** What a courier said when it took the parcel. */
class Consignment
{
    private function __construct(
        public readonly bool $booked,
        public readonly ?string $consignmentId = null,
        public readonly ?string $trackingCode = null,
        public readonly ?string $status = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function success(
        ?string $consignmentId,
        ?string $trackingCode = null,
        ?string $status = null,
        array $raw = [],
    ): self {
        return new self(true, $consignmentId, $trackingCode, $status, null, $raw);
    }

    public static function failure(string $error, array $raw = []): self
    {
        return new self(false, null, null, null, $error, $raw);
    }
}
