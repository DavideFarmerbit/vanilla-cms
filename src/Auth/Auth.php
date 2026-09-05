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
     * Get the current visitor's usernanme.
     * @return string
     * @throws Exception if the auth driver is not set.
     */
    public static function getUsername(): string 
    {
        return self::driver()->getUsername();
    }

    /**
     * Whether the current visitor has 2FA enabled.
     * @return bool
     * @throws Exception if the auth driver is not set.
     */
    public static function has2FA(): bool
    {
        return self::driver()->has2FA();
    }

    /**
     * Logs the current user out.
     * @throws Exception if the auth driver is not set.
     */
    public static function logout(): void
    {
        self::driver()->logout();
    }

    /**
     * Attempts to log in with the given credentials.
     * @throws SecondFactorRequiredException if credentials were correct but a 2FA challenge was started.
     * @throws AuthException if the credentials were invalid.
     * @throws Exception if the auth driver is not set.
     */
    public static function login(string $email, string $password): void
    {
        self::driver()->login($email, $password);
    }

    /**
     * Confirms a pending login 2FA challenge.
     * @throws AuthException if the confirmation code is invalid.
     * @throws Exception if the auth driver is not set.
     */
    public static function confirmLoginSecondFactor(string $otp): void
    {
        self::driver()->confirmLoginSecondFactor($otp);
    }

    /**
     * Aborts a pending login 2FA challenge, so the visitor can restart at the credentials step.
     * @throws Exception if the auth driver is not set.
     */
    public static function cancelLoginSecondFactor(): void
    {
        self::driver()->cancelLoginSecondFactor();
    }

    /**
     * Whether a login is currently waiting on a 2FA challenge to be completed.
     * @throws Exception if the auth driver is not set.
     */
    public static function isAwaitingSecondFactor(): bool
    {
        return self::driver()->isAwaitingSecondFactor();
    }

    /**
     * Changes the current user's password.
     * @throws AuthException if the password could not be changed (e.g. wrong old password).
     * @throws Exception if the auth driver is not set.
     */
    public static function changePassword(string $oldPassword, string $newPassword): void
    {
        self::driver()->changePassword($oldPassword, $newPassword);
    }

    /**
     * Starts an email change for the current user. A confirmation link should be sent to the new address;
     * the change only takes effect once confirmEmailChange is called (supposedly from the confirmation link).
     * @throws AuthException if the email could not be changed (e.g. wrong password, address already in use).
     * @throws Exception if the auth driver is not set.
     */
    public static function changeEmail(string $newEmail, string $password): void
    {
        self::driver()->changeEmail($newEmail, $password);
    }

    /**
     * Confirms a pending email change via the selector/token pair from the confirmation link.
     * @throws AuthException if the confirmation link is invalid, expired, or the address is already in use.
     * @throws Exception if the auth driver is not set.
     */
    public static function confirmEmailChange(string $selector, string $token): void
    {
        self::driver()->confirmEmailChange($selector, $token);
    }

    /**
     * Starts enabling 2FA for the current user, sending a confirmation code.
     * @throws AuthException if 2FA setup could not be started (e.g. wrong password).
     * @throws Exception if the auth driver is not set.
     */
    public static function enable2FA(string $password): void
    {
        self::driver()->enable2FA($password);
    }

    /**
     * Confirms a previously requested 2FA setup, activating it.
     * @throws AuthException if the confirmation code is invalid.
     * @throws Exception if the auth driver is not set.
     */
    public static function confirm2FA(string $otp): void
    {
        self::driver()->confirm2FA($otp);
    }

    /**
     * Disables 2FA for the current user.
     * @throws AuthException if 2FA could not be disabled.
     * @throws Exception if the auth driver is not set.
     */
    public static function disable2FA(): void
    {
        self::driver()->disable2FA();
    }

    /**
     * Creates a new user account, already verified.
     * @throws AuthException if the account could not be created (e.g. address already in use).
     * @throws Exception if the auth driver is not set.
     */
    public static function createUser(string $email, string $password): void
    {
        self::driver()->createUser($email, $password);
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
