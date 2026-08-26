<?php

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
}