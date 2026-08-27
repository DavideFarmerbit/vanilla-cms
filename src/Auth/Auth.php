<?php

namespace VanillaCms\Auth;

use Exception;

final class Auth
{
    private static ?AuthDriver $driver = null;

    /**
     * Set the auth driver. Must be called before any other method.
     * @param AuthDriver $driver
     * @return void
     */
    public static function set(AuthDriver $driver): void
    {
        self::$driver = $driver;
    }

    /**
     * Whether the current visitor is allowed to access the admin panel.
     * @return bool
     * @throws Exception if the auth driver is not set.
     */
    public static function isAdmin(): bool
    {
        return self::driver()->isAdmin();
    }

    /**
     * Get the auth driver throwing an exception if not set.
     * @return AuthDriver
     * @throws Exception if auth driver is not set.
     */
    private static function driver(): AuthDriver
    {
        if (self::$driver === null) {
            throw new Exception('VanillaCms >> Auth driver not set');
        }
        return self::$driver;
    }
}
