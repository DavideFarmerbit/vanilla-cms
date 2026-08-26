<?php

namespace VanillaCms\Core\Registry;

final class TypeRegistry
{
    /** @var Page[] */
    private static array $pages = [];
    
    public static function registerPage(string $slug, string $label, string $path, bool $isArchetype, ?callable $urlBuilder = null): void
    {
        $slug = self::sanitizeSlug($slug);
        self::$pages[$slug] = new Page($slug, $label, $path, $isArchetype, $urlBuilder);
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
     * Returns all registered pages which are not archetypes.
     * @return Page[]
     */
    public static function simplePages(): array
    {
        return array_filter(self::$pages, fn (Page $page) => !$page->isArchetype());
    }

    /**
     * Returns all registered pages which are archetypes.
     * @return Page[]
     */
    public static function archetypePages(): array
    {
        return array_filter(self::$pages, fn (Page $page) => $page->isArchetype());
    }

    public static function getPage(string $slug): ?Page
    {
        return self::$pages[$slug] ?? null;
    }

    private static function sanitizeSlug(string $string): string
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
