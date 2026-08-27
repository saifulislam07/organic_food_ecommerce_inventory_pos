<?php

return [
    /*
     | Fallback driver when nothing is configured in the admin panel.
     | 'log' writes messages to the log instead of spending SMS credit.
     */
    'default' => env('SMS_DRIVER', 'log'),

    'log_channel' => env('SMS_LOG_CHANNEL', config('logging.default')),

    'drivers' => [
        'bulksmsbd' => [
            // Override in the admin panel if your account uses a different host.
            'endpoint' => env('SMS_BULKSMSBD_ENDPOINT', 'https://bulksmsbd.net/api/smsapi'),
        ],
    ],
];
