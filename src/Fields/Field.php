<?php

namespace VanillaCms\Fields;

abstract class Field
{
    public function __construct()
    {
        
    }
    
    /** @return array<string, mixed> */
    public abstract function toArray(): array;
    
    /** @param array<string, mixed> $data */
    public abstract function fromArray(array $data): void;
    
    /** 
     * Render the field's form for Admin Panel.
     * Inputs names MUST be {$name}[$fieldSlug].
     */
    public abstract function render(string $name, array $config): void;
}