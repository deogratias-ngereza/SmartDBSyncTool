<?php

namespace App\Listeners;

use App\Models\Entity;
use App\Models\User;
use App\Utilities\CONST_DEF;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;


class LogSuccessfulLogin
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
    public function handle(Login $event): void
    {
        /**
         * @var User $user
         */
        $user = $event->user;
        // Access the request data if needed, e.g., $event->request

        // Example: Update last login time and IP
        //$user->last_login_at = now();
       // $user->last_login_ip = request()->ip();
       // $user->save();
       Log::info('User logged in: ' . $user->entity_id);

       $entityInfo = Entity::where("id",$user->entity_id)->first();

       //session(CONST_DEF::$SESSION_ENTITY_ID,$user->entity_id);
       request()->session()->put(CONST_DEF::$SESSION_ENTITY_ID,$user->entity_id);
       request()->session()->put(CONST_DEF::$SESSION_ENTITY_DATA,$entityInfo);
    }
}
