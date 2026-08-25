<?php

namespace VanillaCms\Core\Router;

use Closure;

class RouterDispatcher
{
    private string $pattern;
    private Closure $handler;
    
    public function __construct(string $pattern, Closure $handler)
    {
        $this->pattern = $pattern;
        $this->handler = $handler;
    }
    
    public function pattern(): string
    {
        return $this->pattern;
    }
    
    public function handle(array $params): void
    {
        ($this->handler)(...$params);
    }
}