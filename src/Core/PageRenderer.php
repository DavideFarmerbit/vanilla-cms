<?php

namespace VanillaCms\Core;

use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\Router;

final class PageRenderer
{
    public static function page(string $slug): void
    {
        $page = TypeRegistry::getPage($slug);

        if (!$page) {
            Router::notFound();
            return;
        }

        self::render($page->path(), ['page' => $page]);
    }

    public static function templateInstance(string $typeSlug, string $instanceSlug): void
    {
        $template = TypeRegistry::getTemplate($typeSlug);

        if (!$template) {
            Router::notFound();
            return;
        }
        
        self::render($template->path(), ['template' => $template, 'instanceSlug' => $instanceSlug]);
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
