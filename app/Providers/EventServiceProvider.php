<?php

namespace App\Providers;
use App\Listeners\LoggedOutListener;
use App\Listeners\LogSuccessfulLogin;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        // Other events...
        //Login::class => [ LogSuccessfulLogin::class ],
        //Logout::class => [ LoggedOutListener::class ],
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
