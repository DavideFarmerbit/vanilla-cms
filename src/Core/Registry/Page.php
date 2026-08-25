<?php

namespace VanillaCms\Core\Registry;

class Page
{
    private string $slug;
    private string $label;
    private string $path;
    
    public function __construct(string $slug, string $label, string $path)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->path = $path;
    }
    
    public function slug(): string
    {
        return $this->slug;
    }
    
    public function label(): string
    {
        return $this->label;
    }
    
    public function path(): string
    {
        return $this->path;
    }
}