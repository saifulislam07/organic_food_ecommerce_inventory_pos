<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Notifications\Channels\SmsChannel;
use App\Observers\ProductVariantObserver;
use App\Sms\SmsManager;
use App\Support\AdminModules;
use App\Support\MailSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsManager::class);
    }

    public function boot(): void
    {
        // Every admin list paginates, and the framework default emits Tailwind
        // markup this Bootstrap panel has no styles for, so the links rendered
        // as bare text.
        Paginator::useBootstrapFive();

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

        // Stock arriving is what a pre-order has been waiting for, and it can
        // arrive from a purchase, an adjustment or a stock edit — an observer
        // catches all three rather than each of them remembering to tell.
        ProductVariant::observe(ProductVariantObserver::class);

        // The storefront header carries an "All Categories" menu on every page,
        // so the list is composed into the layout rather than repeated in each
        // controller. Named apart from the page's own $categories on purpose.
        View::composer('layouts.frontend', function ($view) {
            $view->with('navCategories', Category::active()->sorted()->withCount('products')->get());
        });
    }
}
