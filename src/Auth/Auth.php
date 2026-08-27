<?php

namespace VanillaCms\Auth;

use Exception;

final class Auth
{
    private static ?AuthDriver $driver = null;
    private static string $unauthorizedUrl = '/';

    /**
     * Set the auth driver. Must be called before any other method.
     * @param AuthDriver $driver class implementing auth checks.
     * @param string $unauthorizedUrl url to redirect to if the user is not authorized.
     * @return void
     */
    public static function set(AuthDriver $driver, string $unauthorizedUrl): void
    {
        self::$driver = $driver;
        self::$unauthorizedUrl = $unauthorizedUrl;
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
     * Get the url to redirect to if the user is not authorized.
     * @return string
     */
    public static function unauthorizedUrl(): string
    {
        return self::$unauthorizedUrl;
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
