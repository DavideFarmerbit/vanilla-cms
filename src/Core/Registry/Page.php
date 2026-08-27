<?php

namespace VanillaCms\Core\Registry;

use Closure;
use VanillaCms\Fields\Field;
use VanillaCms\Fields\FieldReflection;
use VanillaCms\Storage\PageData;

/** 
 * Parent class for all Pages in the website.
 * Override the render() method to render the page.
 * Add Field members to the class to make them available to the page, and be able to edit them in the admin panel.
 */
abstract class Page
{
    private string $slug;
    private string $label;
    private bool $isArchetype = false;
    private Closure $urlBuilder;
    
    public function __construct(string $slug, string $label, bool $isArchetype, ?callable $urlBuilder = null)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->isArchetype = $isArchetype;
        
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
    
    public function url(PageData $data): string
    {
        return ($this->urlBuilder)($data);
    }
    
    public function isArchetype(): bool {
        return $this->isArchetype;
    }
    
    /** Override to render the page. */
    public abstract function render(PageData $data): void;
    
    /**
     * Get all the Fields of this class.
     * @return array<string, Field> Name => Field
     */
    public function getFields(): array
    {
        return FieldReflection::getFields($this);
    }
}