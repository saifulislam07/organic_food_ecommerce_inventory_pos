<?php

namespace App\Sms\Drivers;

use App\Sms\Contracts\SmsDriver;
use App\Sms\SmsResult;
use Illuminate\Support\Facades\Log;

/**
 * Writes the message to the log instead of sending it. The default, so a fresh
 * install never silently burns SMS credit while someone is testing.
 */
class LogDriver implements SmsDriver
{
    public function send(string $to, string $message): SmsResult
    {
        Log::channel(config('sms.log_channel'))->info('SMS', [
            'to' => $to,
            'message' => $message,
        ]);

        return SmsResult::success('logged');
    }

    public function name(): string
    {
        return 'log';
    }
}
