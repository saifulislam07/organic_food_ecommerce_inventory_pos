<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The seeded administrator
    |--------------------------------------------------------------------------
    |
    | AdminSeeder guarantees this account exists and can reach /admin. Set
    | ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME in .env on a live site so the
    | credentials never live in the repository.
    |
    | Read through config rather than env() directly, so `php artisan
    | config:cache` does not blank them out.
    |
    */

    'email' => env('ADMIN_EMAIL', 'admin@gmail.com'),

    'name' => env('ADMIN_NAME', 'Admin'),

    'password' => env('ADMIN_PASSWORD', '111111'),

    /*
    |--------------------------------------------------------------------------
    | Prefill the admin login form
    |--------------------------------------------------------------------------
    |
    | On a development machine the gateway hands you the seeded credentials
    | already typed in, so logging in is one click. It is bound to APP_ENV
    | rather than a switch of its own: printing a password into a page is only
    | ever acceptable locally, and nothing about staging or production should
    | be able to turn it back on by accident.
    |
    */

    'prefill_login' => env('APP_ENV', 'production') === 'local',

];
