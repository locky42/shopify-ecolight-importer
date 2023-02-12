<?php

namespace App\Helpers\Api;

use  Shopify\Auth\Session as AuthSession;

class Session
{
    protected static AuthSession $session;

    /**
     * @param AuthSession $session
     * @return void
     */
    public static function setSession(AuthSession $session): void
    {
        self::$session = $session;
    }

    /**
     * @return AuthSession
     */
    public static function get(): AuthSession
    {
        return self::$session;
    }
}
