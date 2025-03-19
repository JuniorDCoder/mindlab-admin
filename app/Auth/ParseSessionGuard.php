<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

class ParseSessionGuard extends SessionGuard
{
    /**
     * Override the check to handle our custom Parse user
     */
    public function check()
    {
        return session()->has('sessionToken') && $this->user() !== null;
    }

    /**
     * Get the currently authenticated user.
     */
    public function user()
    {
        // Return the user from the session if we've already retrieved it
        if (!is_null($this->user)) {
            return $this->user;
        }

        // Get the user from the session
        $parseUser = session('user');

        if ($parseUser) {
            $user = new \App\Models\ParseUserAdapter($parseUser);
            $this->user = $user;
            return $user;
        }

        return null;
    }
}