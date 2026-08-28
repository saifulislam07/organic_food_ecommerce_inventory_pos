<?php

namespace App\Notifications\Channels;

use App\Sms\SmsManager;
use Illuminate\Notifications\Notification;

/**
 * Lets a notification declare `toSms()` and be routed through whichever gateway
 * the shop has configured. Registered as the "sms" channel.
 */
class SmsChannel
{
    public function __construct(private readonly SmsManager $sms) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $number = $notifiable->routeNotificationFor('sms', $notification);

        if (blank($number)) {
            return;
        }

        $message = (string) $notification->toSms($notifiable);

        if (trim($message) === '') {
            return;
        }

        $this->sms->send($number, $message);
    }
}
