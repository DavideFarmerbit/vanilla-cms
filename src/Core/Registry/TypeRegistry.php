<?php

namespace VanillaCms\Core\Registry;

final class TypeRegistry
{
    /** @var Page[] */
    private static array $pages = [];
    /** @var PageTemplate[] */
    private static array $templates = [];
    
    public static function registerPage(string $slug, string $label, string $path): void
    {
        self::$pages[$slug] = new Page($slug, $label, $path);
    }

    public static function registerTemplate(string $slug, string $label, string $path): void
    {
        self::$templates[$slug] = new PageTemplate($slug, $label, $path);
    }

    /** 
     * Returns all registered pages.
     * @return Page[]
     */
    public static function pages(): array
    {
        return self::$pages;
    }

    /**
     * Returns all registered templates.
     * @return PageTemplate[]
     */
    public static function templates(): array
    {
        return self::$templates;
    }

    public static function getPage(string $slug): ?Page
    {
        return self::$pages[$slug] ?? null;
    }

    public static function getTemplate(string $slug): ?PageTemplate
    {
        return self::$templates[$slug] ?? null;
    }
}
