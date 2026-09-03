<?php

namespace VanillaCms\Auth;

/**
 * Thrown by AuthDriver::login() when credentials were correct but a second factor challenge has
 * just been started. The message describes what happened (e.g. "code sent to ...") and is meant
 * to be shown to the user; isError() tells the caller whether to present it as an error or as info
 * (e.g. the challenge started, but delivering the code itself failed).
 */
final class SecondFactorRequiredException extends AuthException
{
    private bool $isError;

    public function __construct(string $message, bool $isError = false)
    {
        parent::__construct($message);
        $this->isError = $isError;
    }

    public function isError(): bool
    {
        return $this->isError;
    }
}
