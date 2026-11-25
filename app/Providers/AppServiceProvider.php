<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Behind HTTPS (and under a sub-path like /cpsu-oas), build every link from APP_URL so emails,
        // Google callbacks and assets never fall back to http:// or the wrong path.
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // MAIL_MAILER=gmail sends as the connected system Google account.
        \Illuminate\Support\Facades\Mail::extend('gmail', fn () => new \App\Mail\GmailApiTransport(app(\App\Services\GoogleDrive::class)));
    }
}
