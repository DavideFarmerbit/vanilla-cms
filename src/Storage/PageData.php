<?php

namespace VanillaCms\Storage;

use VanillaCms\Pages\Page;

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
    
    /** array view of the fields of the page (Field::toArray) */
    public array $fields = [];


    public static function empty(): self
    {
        $pageData = new self();
        
        $pageData->type_slug = '';
        $pageData->type_label = '';
        $pageData->is_archetype = false;

        $pageData->id = '';
        $pageData->slug = '';
        $pageData->name = '';

        $pageData->fields = [];
        
        return $pageData;
    }
    
    public function setPage(Page $page): void
    {
        $this->type_slug = $page->slug();
        $this->type_label = $page->label();
        $this->is_archetype = $page->isArchetype();
    }
}