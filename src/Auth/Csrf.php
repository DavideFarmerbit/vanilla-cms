<?php

namespace VanillaCms\Auth;

use Exception;

final class Csrf
{
    private const SESSION_KEY = 'vanilla_cms_csrf_token';

    /**
     * Get the current session's CSRF token, generating one if none exists yet.
     * @return string
     * @throws Exception if there is no active session.
     */
    public static function token(): string
    {
        self::assertSessionActive();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Verify a submitted token against the current session's token, in constant time.
     * @param string|null $submittedToken
     * @return bool
     * @throws Exception if there is no active session.
     */
    public static function verify(?string $submittedToken): bool
    {
        self::assertSessionActive();

        if ($submittedToken === null || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $submittedToken);
    }

    private static function assertSessionActive(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new Exception('VanillaCms >> Csrf requires an active session. Call session_start() before dispatching.');
        }
    }
}
