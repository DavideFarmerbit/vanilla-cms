<?php

namespace VanillaCms\Core\Registry;

class PageInstanceMeta
{
    private string $id;
    private string $slug;
    private string $name;
    
    public function __construct(string $id, string $slug, string $name)
    {
        $this->id = $id;
        $this->slug = $slug;
        $this->name = $name;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }
}