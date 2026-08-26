<?php

namespace VanillaCms\Core;

use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Storage\PageData;
use VanillaCms\Storage\Storage;
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
        
        // Get the page data for the specified instance, or the first instance if none is specified.
        $pageData = $instanceSlug !== null ? Storage::findBySlug($typeSlug, $instanceSlug) : Storage::findFirst($typeSlug);
        self::render($page->path(), $pageData?? PageData::fromPage($page, '', ''));
    }

    private static function render(string $filePath, PageData $data): void
    {
        if (!file_exists($filePath)) {
            Router::notFound();
            return;
        }
        
        require $filePath;
    }
}
