<?php

namespace App\Notifications;

use App\Support\MailSettings;
use App\Support\SmsSettings;

/**
 * Which channels a customer notification should actually use.
 *
 * A shop that has not set up SMTP or a gateway yet should not have its queue
 * fill with failures, so an unconfigured channel is simply skipped.
 */
class NotificationChannels
{
    public static function forCustomer(object $notifiable): array
    {
        $channels = [];

        if (MailSettings::isConfigured() && filled($notifiable->routeNotificationFor('mail'))) {
            $channels[] = 'mail';
        }

        if (SmsSettings::isConfigured() && filled($notifiable->routeNotificationFor('sms'))) {
            $channels[] = 'sms';
        }

        return $channels;
    }
}
