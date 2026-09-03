<?php

namespace VanillaCms\Fields;

abstract class Field
{
    protected array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function priority(): int
    {
        return $this->config['vcms-priority'] ?? 0;
    }
    
    /** @return array<string, mixed> */
    public abstract function toArray(): array;
    
    /** @param array<string, mixed> $data */
    public abstract function fromArray(array $data): void;
    
    /** 
     * Render the field's form for Admin Panel.
     * Inputs names MUST be {$name}[$fieldSlug].
     * @param string $name Name of the field.
     */
    public abstract function render(string $name): void;
}