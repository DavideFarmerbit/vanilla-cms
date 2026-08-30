<?php

namespace VanillaCms\Pages;

class PageMeta
{
    private string $id;
    private string $slug;
    private string $name;
    private Pagevisibility $visibility;
    
    public function __construct(string $id, string $slug, string $name, Pagevisibility $visibility)
    {
        $this->id = $id;
        $this->slug = $slug;
        $this->name = $name;
        $this->visibility = $visibility;
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
    
    public function visibility(): Pagevisibility
    {
        return $this->visibility;
    }
}