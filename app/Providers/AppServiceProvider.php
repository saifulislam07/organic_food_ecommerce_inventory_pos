<?php

namespace App\Providers;

use App\Notifications\Channels\SmsChannel;
use App\Sms\SmsManager;
use App\Support\AdminModules;
use App\Support\MailSettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsManager::class);
    }

    public function boot(): void
    {
        // SMTP credentials are managed from the admin panel, not .env.
        MailSettings::apply();

        // Super Admin passes every permission check, so new modules never need
        // the role to be re-granted.
        Gate::before(fn ($user) => $user->hasRole(AdminModules::SUPER_ADMIN) ? true : null);

        // Lets a notification declare toSms() and reach the configured gateway.
        Notification::extend('sms', fn ($app) => $app->make(SmsChannel::class));
    }
}
