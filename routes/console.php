<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 | Ask the couriers where their parcels are, so nobody has to type "delivered"
 | into an order by hand.
 |
 | Hourly rather than more often on purpose: parcel statuses move over days, the
 | providers rate-limit, and a shop with a backlog would otherwise spend its API
 | quota re-reading the same parcels. It costs nothing when no courier is set up
 | — the command says so and stops. withoutOverlapping because a slow provider
 | must not have two sweeps running against it at once.
 */
Schedule::command('couriers:sync')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
