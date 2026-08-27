<?php

namespace App\Providers;

use App\Support\MailSettings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // SMTP credentials are managed from the admin panel, not .env.
        MailSettings::apply();
    }
}
