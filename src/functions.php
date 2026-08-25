<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Core\PageRenderer;
use VanillaCms\Core\Router\RouterDispatcher;
use VanillaCms\Core\TypeRegistry;

function DefinePage(string $slug, string $label): void
{
    TypeRegistry::registerPage($slug, $label);
}

function DefinePageTemplate(string $slug, string $label): void
{
    TypeRegistry::registerTemplate($slug, $label);
}

/** 
 * Shortend for creating a new router dispatcher.
 */
function router_dispatcher(string $pattern, callable $handler): RouterDispatcher
{
    return new RouterDispatcher($pattern, $handler);
}

/** 
 * Returns a set of default router dispatchers.
 * @return RouterDispatcher[]
 */
function default_router_dispatchers(string $pagesDir): array {
    return [
        // Homepage
        router_dispatcher('', fn () => PageRenderer::page($pagesDir, 'home')),
        // Pages
        ...array_map(
            fn ($page) => router_dispatcher(
                $page['slug'],
                fn () => PageRenderer::page($pagesDir, $page['slug'])
            ),
            TypeRegistry::pages()
        ),
        // Templates
        ...array_map(
            fn ($template) => router_dispatcher(
                $template['slug'] . '/{item}',
                fn (string $item) => PageRenderer::templateInstance($pagesDir, $template['slug'], $item)
            ),
            TypeRegistry::templates()
        ),
        // Admin pannel
        router_dispatcher('admin/*', fn (array $segments) => AdminController::dispatch($segments)),
    ];
}