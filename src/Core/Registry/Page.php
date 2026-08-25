<?php

namespace VanillaCms\Core\Registry;

use Closure;

class Page
{
    private string $slug;
    private string $label;
    private string $path;
    private Closure $urlBuilder;
    
    public function __construct(string $slug, string $label, string $path, ?callable $urlBuilder = null)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->path = $path;
        $this->urlBuilder = $urlBuilder ?? fn () => '/' . $slug;
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
    
    public function url(array $data): string
    {
        return ($this->urlBuilder)($data);
    }
}