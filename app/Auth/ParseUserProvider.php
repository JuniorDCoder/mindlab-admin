<?php

namespace App\Auth;

use Parse\ParseUser;
use App\Models\ParseUserAdapter;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class ParseUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        // Not implementing this as we mainly use session tokens
        return null;
    }

    public function retrieveByToken($identifier, $token)
    {
        // Not implementing this for now
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Not implementing this for now
    }

    public function retrieveByCredentials(array $credentials)
    {
        // Not implementing this as we use Parse's login
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Not implementing this as we use Parse's validation
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $confirmed = true): bool
    {
        // Back4App/Parse handles password hashing, so we don't need to rehash
        return false;
    }
}