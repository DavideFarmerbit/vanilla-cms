<?php

namespace VanillaCms\Admin;

abstract class AdminTab
{
    private string $slug;
    private string $label;
    
    public function __construct(string $slug, string $label)
    {
        $this->slug = $slug;
        $this->label = $label;
    }
    
    public function fullSlug(): string {
        return $this->slug();
    }
    
    public final function slug(): string {
        return $this->slug;
    }
    
    public final function label(): string {
        return $this->label;
    }
    
    public final function url(): string {
        return "/admin/{$this->fullSlug()}";
    }
    
    public function handleApiRequest(array $segments): bool {
        return false;
    }

    public abstract function dispatch(array $segments): void;
}