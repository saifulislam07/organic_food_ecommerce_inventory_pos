<?php

namespace App\Sms;

/** What a driver reports back after trying to deliver one message. */
class SmsResult
{
    private function __construct(
        public readonly bool $sent,
        public readonly ?string $reference = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public static function success(?string $reference = null, array $raw = []): self
    {
        return new self(true, $reference, null, $raw);
    }

    public static function failure(string $error, array $raw = []): self
    {
        return new self(false, null, $error, $raw);
    }
}
