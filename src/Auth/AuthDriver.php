<?php

namespace VanillaCms\Auth;

interface AuthDriver
{
    public function isAdmin(): bool;
    
    public function has2FA(): bool;
    
}
