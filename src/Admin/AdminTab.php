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
    
    public function slug(): string {
        return $this->slug;
    }
    
    public function label(): string {
        return $this->label;
    }
    
    public function handleApiRequest(array $segments): bool {
        
    }

    public abstract function dispatch(array $segments): void;
}