<?php

namespace VanillaCms\Core\Registry;

class PageTemplate extends Page
{
    public function __construct(string $slug, string $label, string $path, ?callable $urlBuilder = null)
    {
        parent::__construct($slug, $label, $path, $urlBuilder ?? fn (array $data) => '/' . $slug . '/' . $data['slug']);
    }
}