<?php

use App\Courier\Drivers\ManualCourierDriver;
use App\Courier\Drivers\PathaoDriver;
use App\Courier\Drivers\RedxDriver;
use App\Courier\Drivers\SteadfastDriver;

return [

    /*
    |--------------------------------------------------------------------------
    | The couriers this shop can use
    |--------------------------------------------------------------------------
    |
    | Every driver listed here gets a panel on the courier settings page and a
    | row in the "send to courier" picker, automatically. Adding a company means
    | one class implementing CourierDriver and one line here — nothing else in
    | the application needs to learn its name.
    |
    | Which of them are actually switched on, and which is preferred, is a
    | setting rather than config: the shop owner changes couriers far more often
    | than anyone deploys.
    |
    */

    'drivers' => [
        SteadfastDriver::key() => [
            'class' => SteadfastDriver::class,
            'base_url' => env('COURIER_STEADFAST_URL', 'https://portal.packzy.com/api/v1'),
        ],

        PathaoDriver::key() => [
            'class' => PathaoDriver::class,
            'base_url' => env('COURIER_PATHAO_URL', 'https://api-hermes.pathao.com'),
        ],

        RedxDriver::key() => [
            'class' => RedxDriver::class,
            'base_url' => env('COURIER_REDX_URL', 'https://openapi.redx.com.bd/v1.0.0-beta'),
        ],

        ManualCourierDriver::key() => [
            'class' => ManualCourierDriver::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Status sync
    |--------------------------------------------------------------------------
    |
    | couriers:sync asks every courier where its parcels are. `statuses` is the
    | set of order states worth asking about — an order already delivered or
    | cancelled has nothing left to learn. `batch` caps one run, so a shop with
    | a large backlog spends a predictable number of API calls per sweep rather
    | than hammering a provider that rate-limits.
    |
    */

    'sync' => [
        'statuses' => ['confirmed', 'processing', 'shipped'],
        'batch' => env('COURIER_SYNC_BATCH', 100),
    ],

];
