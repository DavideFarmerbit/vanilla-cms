<?php

namespace VanillaCms\Pages;

use Closure;
use Exception;
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
    
    private ?PageMeta $meta = null;
    
    public function __construct(string $slug, string $label, bool $isArchetype, ?callable $urlBuilder = null)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->isArchetype = $isArchetype;
        
        $this->urlBuilder = $urlBuilder ?? (!$isArchetype 
            ? (fn () => '/' . $slug) 
            : (fn ($instance) => '/' . $slug . '/' . $instance->meta?->slug() ?? '')
        );
    }
    
    public function instantiate(PageData $data): Page
    {
        $instance = new $this();

        // Fill meta data
        $instance->meta = new PageMeta($data->id, $data->slug, $data->name, $data->visibility);
        
        // Fill fields
        foreach ($instance->getFields() as $fieldName => $field) {
            $field->fromArray($data->fields[$fieldName] ?? []);
        }
        
        return $instance;
    }
    
    public function toPageData(): PageData
    {
        $pageData = PageData::empty();
        $pageData->setPage($this);

        $pageData->id = '';
        $pageData->slug = $this?->meta?->slug() ?? '';
        $pageData->name = $this?->meta?->name() ?? '';
        $pageData->visibility = $this?->meta?->visibility() ?? PageVisibility::HIDDEN;

        $pageData->fields = array_map(fn ($field) => $field->toArray(), $this->getFields());

        return $pageData;
    }

    /*================================================================================================================*/
    // DataInterface
    
    public function slug(): string
    {
        return $this->slug;
    }
    
    public function label(): string
    {
        return $this->label;
    }
    
    /**
     * @throws Exception if called on a prototype object.
     */
    public function url(): string
    {
        if ($this->meta === null) {
            throw new Exception("Cannot access url for a page prototype object");
        }
        
        return ($this->urlBuilder)($this);
    }
    
    public function isArchetype(): bool {
        return $this->isArchetype;
    }
    
    /**
     * @throws Exception if called on a prototype object.
     */
    public function meta(): PageMeta {
        if ($this->meta === null) {
            throw new Exception("Cannot access meta for a page prototype object");
        }
        
        return $this->meta;
    }

    // ~DataInterface
    /*================================================================================================================*/
    
    /*================================================================================================================*/
    // Rendering
    
    /**
     * @throws Exception if called on a prototype object.
     */
    public final function render(): void {
        if ($this->meta === null) {
            throw new Exception("Cannot render a page prototype object");
        }

        $this->render_internal();
    }
    
    /** 
     * Override to render the page. 
     * @throws Exception if called on a prototype object, and implementation uses instance data.
     */
    protected abstract function render_internal(): void;

    // ~Rendering
    /*================================================================================================================*/

    /*================================================================================================================*/
    // Reflection
    
    /**
     * Get all the Fields of this class.
     * @return array<string, Field> Name => Field
     */
    public final function getFields(): array
    {
        return FieldReflection::getFields($this);
    }

    // ~Reflection
    /*================================================================================================================*/
}