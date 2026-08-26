<?php

namespace VanillaCms\Core;

use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\Router;

final class PageRenderer
{
    public static function page(string $typeSlug, ?string $instanceSlug = null): void
    {
        $page = TypeRegistry::getPage($typeSlug);

        if (!$page) {
            Router::notFound();
            return;
        }
        
        // TODO: if $instanceSlug is null, we render the first instance of the page. Usefull for non archetype pages.
        
        self::render($page->path(), ['page' => $page, 'instanceSlug' => $instanceSlug]);
    }

    private static function render(string $filePath, array $data): void
    {
        // TODO: data should be the actual data from storage and the page interprets it in fields objects, or do we want it to be some metadata + field objects directly
        
        if (!file_exists($filePath)) {
            Router::notFound();
            return;
        }

        extract($data);
        require $filePath;
    }
}
