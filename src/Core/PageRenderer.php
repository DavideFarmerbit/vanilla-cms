<?php

namespace VanillaCms\Core;

use VanillaCms\Core\Registry\PageTypeRegistry;
use VanillaCms\Storage\PageData;
use VanillaCms\Storage\Storage;
use VanillaCms\Core\Router\Router;

final class PageRenderer
{
    public static function page(string $typeSlug, ?string $instanceSlug = null): void
    {
        $page = PageTypeRegistry::getPageType($typeSlug);

        if (!$page) {
            Router::notFound();
            return;
        }
        
        // Get the page data for the specified instance, or the first instance if none is specified.
        $pageData = $instanceSlug !== null ? Storage::findPageInstanceBySlug($typeSlug, $instanceSlug) : Storage::findFirstPageInstance($typeSlug);
        if (!$pageData) {
            Router::notFound();
            return;
        }
        
        $page->instantiate($pageData)->render();
    }
}
