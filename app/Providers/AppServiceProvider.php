<?php

namespace App\Providers;

use App\Models\Setting;
use App\Notifications\Channels\SmsChannel;
use App\Sms\SmsManager;
use App\Support\AdminModules;
use App\Support\MailSettings;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
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

        // A web request is a fresh process, but a queue worker is not: without
        // this it would keep serving the settings it read hours ago.
        Event::listen(JobProcessing::class, function () {
            Setting::flush();
            MailSettings::apply();
        });

        // Lets a notification declare toSms() and reach the configured gateway.
        Notification::extend('sms', fn ($app) => $app->make(SmsChannel::class));
    }
}
