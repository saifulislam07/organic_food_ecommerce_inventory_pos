<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landing page URL prefix
    |--------------------------------------------------------------------------
    |
    | Every campaign page lives under this segment: /lp/eid-mango-combo.
    |
    | The prefix is read when routes are registered, so `php artisan route:cache`
    | freezes whatever is set here — change it in this file and clear the route
    | cache, rather than expecting a settings screen to move live URLs.
    |
    | A prefix also keeps campaign slugs out of the way of the catch-all route
    | at the bottom of routes/web.php, which owns every single-segment path.
    |
    */

    'prefix' => env('LANDING_PREFIX', 'lp'),

    /*
    |--------------------------------------------------------------------------
    | Order rate limit
    |--------------------------------------------------------------------------
    |
    | Orders accepted from one IP per minute. Ad traffic brings bots, and the
    | order form is public, unauthenticated and writes to the database.
    |
    */

    'order_rate_limit' => env('LANDING_ORDER_RATE_LIMIT', 8),

];
