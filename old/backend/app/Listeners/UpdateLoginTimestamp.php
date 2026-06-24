<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLoginTimestamp
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
     * Updates login timestamps to track current and previous login times.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Move current login to last login (2nd last logic)
        $user->last_login_at = $user->current_login_at;
        
        // Update current login
        $user->current_login_at = now();
        $user->last_login_ip = request()->ip();
        
        $user->save();
    }
}
