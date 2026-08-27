<?php

namespace App\Sms\Contracts;

use App\Sms\SmsResult;

interface SmsDriver
{
    /**
     * @param  string  $to  Recipient in international form, e.g. 8801712345678
     */
    public function send(string $to, string $message): SmsResult;

    /** Short name shown in the admin panel and logs. */
    public function name(): string;
}
