<?php

namespace App\Sms;

use App\Sms\Contracts\SmsDriver;
use App\Sms\Drivers\BulkSmsBdDriver;
use App\Sms\Drivers\LogDriver;
use App\Support\SmsSettings;
use Illuminate\Support\Facades\Log;

/**
 * Resolves the configured gateway and hands it a normalised number.
 * Adding a provider means one new class plus one line in driver().
 */
class SmsManager
{
    private ?SmsDriver $override = null;

    public function send(?string $to, string $message): SmsResult
    {
        $number = self::normalise($to);

        if ($number === null) {
            return SmsResult::failure('No usable mobile number.');
        }

        if (trim($message) === '') {
            return SmsResult::failure('Message is empty.');
        }

        $result = $this->driver()->send($number, $message);

        if (! $result->sent) {
            Log::warning('SMS failed', ['to' => $number, 'error' => $result->error]);
        }

        return $result;
    }

    public function driver(): SmsDriver
    {
        if ($this->override) {
            return $this->override;
        }

        $settings = SmsSettings::all();

        return match ($settings['sms_driver'] ?? 'log') {
            'bulksmsbd' => new BulkSmsBdDriver(
                apiKey: (string) ($settings['sms_api_key'] ?? ''),
                senderId: (string) ($settings['sms_sender_id'] ?? ''),
                endpoint: (string) ($settings['sms_endpoint'] ?: config('sms.drivers.bulksmsbd.endpoint')),
            ),
            default => new LogDriver,
        };
    }

    /** Swap in a driver for tests, or to try credentials before saving them. */
    public function use(?SmsDriver $driver): self
    {
        $this->override = $driver;

        return $this;
    }

    /**
     * Bangladeshi numbers get typed as 01712345678, +8801712345678 or
     * 8801712345678; gateways want the last form.
     */
    public static function normalise(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        if ($digits === '') {
            return null;
        }

        // 01712345678 -> 8801712345678
        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            return '880'.substr($digits, 1);
        }

        // 8801712345678, already international
        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            return $digits;
        }

        // 1712345678, missing the leading zero
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            return '880'.$digits;
        }

        // Anything else is passed through — foreign numbers are the caller's problem.
        return strlen($digits) >= 8 ? $digits : null;
    }
}
