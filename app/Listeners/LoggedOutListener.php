<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Log;


class LoggedOutListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {

        /**
         * @var User $user
         */
        $user = $event->user;
        //
        // $event->user is the user who just logged out
        if ($user) {
            Log::info('User logged out: ' . $user->id);
            
            // Example: update a status column
            // $event->user->update(['is_online' => false]);
        }
    }
}
