<?php

namespace App\Providers;

use App\Notifications\WelcomeVerifiedNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(Verified::class, function (Verified $event): void {
            $event->user->notify(new WelcomeVerifiedNotification);
        });
    }
}
