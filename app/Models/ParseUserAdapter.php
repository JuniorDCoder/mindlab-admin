<?php

namespace App\Models;

use Parse\ParseUser;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class ParseUserAdapter implements AuthenticatableContract
{
    use Authenticatable;

    protected $parseUser;
    protected $attributes = [];

    public function __construct(ParseUser $parseUser)
    {
        $this->parseUser = $parseUser;
        $this->attributes['id'] = $parseUser->getObjectId();
        $this->attributes['email'] = $parseUser->get('email');
        $this->attributes['password'] = ''; // We don't store the password
    }

    public function getParseUser()
    {
        return $this->parseUser;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->parseUser->getObjectId();
    }

    public function getAuthPassword()
    {
        return ''; // We don't use Laravel's password system
    }

    public function getRememberToken()
    {
        return '';
    }

    public function setRememberToken($value)
    {
        // Not implemented
    }

    public function getRememberTokenName()
    {
        return '';
    }
}