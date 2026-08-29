<?php

namespace VanillaCms\Core\Registry;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/** 
 * Stores the default constructed pages. 
 */
final class PageTypeRegistry
{
    /** @var Page[] */
    private static array $pages = [];
    
    /**
     * Registers a new page type.
     * @param class-string<Page> $pageClass
     */
    public static function registerType(string $pageClass): void
    {
        // Check class is a Page.
        if (!is_subclass_of($pageClass, Page::class)) {
            throw new InvalidArgumentException("$pageClass must extend Page");
        }

        // Get the constructor from reflection.
        try {
            $reflection = new ReflectionClass($pageClass);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException("$pageClass does not exist");
        }
        $constructor = $reflection->getConstructor();

        // Check class has zero params constructor.
        if ($constructor !== null && $constructor->getNumberOfParameters() > 0) {
            throw new InvalidArgumentException(
                "$pageClass must have a constructor with no parameters"
            );
        }

        // Check class is instantiable.
        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException("$pageClass must be instantiable (not abstract/interface)");
        }
        
        // Instantiate the class and register it
        $page = new $pageClass();
        self::$pages[$page->slug()] = $page;
    }

    /** 
     * Returns all registered pages.
     * @return Page[]
     */
    public static function types(): array
    {
        return self::$pages;
    }

    /**
     * Returns all registered pages which are not archetypes.
     * @return Page[]
     */
    public static function simpleTypes(): array
    {
        return array_filter(self::$pages, fn (Page $page) => !$page->isArchetype());
    }

    /**
     * Returns all registered pages which are archetypes.
     * @return Page[]
     */
    public static function archetypeTypes(): array
    {
        return array_filter(self::$pages, fn (Page $page) => $page->isArchetype());
    }

    public static function getPageType(string $slug): ?Page
    {
        return self::$pages[$slug] ?? null;
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
