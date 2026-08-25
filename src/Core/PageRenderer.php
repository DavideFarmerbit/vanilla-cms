<?php

namespace VanillaCms\Core;

use VanillaCms\Core\Router\Router;

final class PageRenderer
{
    public static function page(string $pagesDir, string $slug): void
    {
        $page = TypeRegistry::getPage($slug);

        if (!$page) {
            Router::notFound();
            return;
        }

        self::render($pagesDir, $slug, ['page' => $page]);
    }

    public static function templateInstance(string $pagesDir, string $typeSlug, string $instanceSlug): void
    {
        $template = TypeRegistry::getTemplate($typeSlug);

        if (!$template) {
            Router::notFound();
            return;
        }
        
        self::render($pagesDir, $typeSlug, ['template' => $template, 'instanceSlug' => $instanceSlug]);
    }

    private static function render(string $pagesDir, string $slug, array $data): void
    {
        $file = $pagesDir . '/' . $slug . '.php';

        if (!file_exists($file)) {
            Router::notFound();
            return;
        }

        extract($data);
        require $file;
    }
}
