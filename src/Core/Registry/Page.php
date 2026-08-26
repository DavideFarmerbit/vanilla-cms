<?php

namespace VanillaCms\Core\Registry;

use Closure;
use VanillaCms\Storage\PageData;

class Page
{
    private string $slug;
    private string $label;
    private bool $isArchetype = false;

    private string $path;
    private Closure $urlBuilder;
    
    public function __construct(string $slug, string $label, string $path, bool $isArchetype, ?callable $urlBuilder = null)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->isArchetype = $isArchetype;

        $this->path = $path;
        $this->urlBuilder = $urlBuilder ?? (!$isArchetype 
            ? (fn () => '/' . $slug) 
            : (fn (PageData $data) => '/' . $slug . '/' . $data->slug)
        );
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
    
    public function url(PageData $data): string
    {
        return ($this->urlBuilder)($data);
    }
    
    public function isArchetype(): bool {
        return $this->isArchetype;
    }
}