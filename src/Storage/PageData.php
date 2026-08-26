<?php

use VanillaCms\Core\Registry\Page;

class PageData
{
    // Page object data
    public string $type_slug;
    public string $type_label;
    public bool $is_archetype;
    
    // Page instance data
    public string $id;
    public string $slug;
    public string $name;
    
    // Generic container for other data
    public array $data;


    public static function empty(): self
    {
        $pageData = new self();
        
        $pageData->type_slug = '';
        $pageData->type_label = '';
        $pageData->is_archetype = false;

        $pageData->id = '';
        $pageData->slug = '';
        $pageData->name = '';

        $pageData->data = [];
        
        return $pageData;
    }

    public static function fromPage(Page $page, string $slug, string $name): self
    {
        $pageData = new self();
        
        $pageData->setPage($page);

        $pageData->id = '';
        $pageData->slug = $slug;
        $pageData->name = $name;

        $pageData->data = [];
        
        return $pageData;
    }
    
    public function setPage(Page $page): void
    {
        $this->type_slug = $page->slug();
        $this->type_label = $page->label();
        $this->is_archetype = $page->isArchetype();
    }
}