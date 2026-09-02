<?php

namespace VanillaCms\Admin;

use InvalidArgumentException;

class AdminTagGroup
{
    private string $slug;
    private string $label;
    /** @var AdminTab[] */
    private array $tabs = [];

    public function __construct(string $slug, string $label)
    {
        $this->slug = $slug;
        $this->label = $label;
    }

    public function slug(): string {
        return $this->slug;
    }

    public function label(): string {
        return $this->label;
    }
    
    public function registerTab(AdminTab $tab): void {
        if (array_any($this->tabs, fn($t) => $t->slug() === $tab->slug())) {
            throw new InvalidArgumentException("A tab with slug {$tab->slug()} already exists for {$this->slug} group.");
        }
        $this->tabs[] = $tab;
    }
    
    public function tabs(): array {
        return $this->tabs;
    }
}