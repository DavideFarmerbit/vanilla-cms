<?php

namespace VanillaCms\Core\Router;

use Closure;

/**
 * Object consumed by the Router to handle urls properly. Associates a url pattern to an handler function, 
 * which is responsible for rendering the right page.
 */
final class RouterDispatcher
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