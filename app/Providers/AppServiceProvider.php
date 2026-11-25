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
        // MAIL_MAILER=gmail sends as the connected system Google account.
        \Illuminate\Support\Facades\Mail::extend('gmail', fn () => new \App\Mail\GmailApiTransport(app(\App\Services\GoogleDrive::class)));
    }
}
