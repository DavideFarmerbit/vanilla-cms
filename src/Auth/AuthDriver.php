<?php

namespace VanillaCms\Auth;

interface AuthDriver
{
    /** Whether the current visitor is allowed to access the admin panel. */
    public function isAdmin(): bool;

    /** Whether the current visitor has 2FA enabled. */
    public function has2FA(): bool;

    /** Logs the current user out. */
    public function logout(): void;

    /**
     * Attempts to log in with the given credentials.
     * @throws SecondFactorRequiredException if credentials were correct but a 2FA challenge was started.
     * @throws AuthException if the credentials were invalid.
     */
    public function login(string $email, string $password): void;

    /** @throws AuthException if the confirmation code is invalid. */
    public function confirmLoginSecondFactor(string $otp): void;

    /** Aborts a pending login 2FA challenge, so the visitor can restart at the credentials step. */
    public function cancelLoginSecondFactor(): void;

    /** Whether a login is currently waiting on a 2FA challenge to be completed. */
    public function isAwaitingSecondFactor(): bool;

    /** @throws AuthException if the password could not be changed. */
    public function changePassword(string $oldPassword, string $newPassword): void;

    /** @throws AuthException if the email could not be changed. */
    public function changeEmail(string $newEmail, string $password): void;

    /** @throws AuthException if 2FA setup could not be started. */
    public function enable2FA(string $password): void;

    /** @throws AuthException if the confirmation code is invalid. */
    public function confirm2FA(string $otp): void;

    /** @throws AuthException if 2FA could not be disabled. */
    public function disable2FA(): void;

    /** @throws AuthException if the account could not be created (e.g. address already in use). */
    public function createUser(string $email, string $password): void;
}
