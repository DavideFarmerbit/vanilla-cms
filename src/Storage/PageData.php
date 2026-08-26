<?php

class PageData
{
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
}