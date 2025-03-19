<?php

namespace App\Services;

use Parse\ParseClient;
use Parse\ParseUser;
use Parse\ParseException;

class ParseService
{
    public function __construct()
    {
        ParseClient::initialize(env('PARSE_APP_ID'), env('PARSE_REST_API_KEY'), env('PARSE_MASTER_KEY'));
        ParseClient::setServerURL(env('PARSE_API_URL'), '/');
    }

    public function loginUser($email, $password)
    {
        try {
            $user = ParseUser::logIn($email, $password);
            return $user->getSessionToken();
        } catch (ParseException $ex) {
            return [
                'error' => true,
                'message' => $ex->getMessage(),
            ];
        }
    }

    public function getAllObjects($className)
    {
        // Implement this method using Parse SDK if needed
    }

    public function createUser($email, $password, $role = 'admin')
    {
        $user = new ParseUser();
        $user->setUsername($email);
        $user->setPassword($password);
        $user->setEmail($email);
        $user->set("role", $role);

        try {
            $user->signUp();
            return $user;
        } catch (ParseException $ex) {
            return [
                'error' => true,
                'message' => $ex->getMessage(),
            ];
        }
    }
}