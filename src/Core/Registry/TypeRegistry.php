<?php

namespace VanillaCms\Core\Registry;

final class TypeRegistry
{
    /** @var Page[] */
    private static array $pages = [];
    /** @var PageTemplate[] */
    private static array $templates = [];
    
    public static function registerPage(string $slug, string $label, string $path, ?callable $urlBuilder = null): void
    {
        $slug = self::sanitizeSlug($slug);
        self::$pages[$slug] = new Page($slug, $label, $path, $urlBuilder);
    }

    public static function registerTemplate(string $slug, string $label, string $path, ?callable $urlBuilder = null): void
    {
        $slug = self::sanitizeSlug($slug);
        self::$templates[$slug] = new PageTemplate($slug, $label, $path, $urlBuilder);
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

    public static function sanitizeSlug(string $string): string
    {
        // Lowercase
        $slug = strtolower($string);

        // Transliterate accented/unicode chars to ASCII, if intl ext is available
        if (function_exists('transliterator_transliterate')) {
            $slug = transliterator_transliterate('Any-Latin; Latin-ASCII', $slug);
        }

        // Replace anything that's not a-z, 0-9 with a dash
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Trim leading/trailing dashes
        $slug = trim($slug, '-');

        return $slug;
    }
}
