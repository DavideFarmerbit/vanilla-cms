<?php

namespace VanillaCms\Auth;

interface AuthDriver
{
    public function isAdmin(): bool;
}
