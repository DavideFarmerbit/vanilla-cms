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
        router_dispatcher('', fn () => PageRenderer::page($pagesDir, 'home')),
        router_dispatcher('admin/*', fn (array $segments) => AdminController::dispatch($segments)),
        router_dispatcher('{page}', fn (string $slug) => PageRenderer::page($pagesDir, $slug)),
        router_dispatcher('{type}/{item}', fn (string $typeSlug, string $itemSlug) => PageRenderer::templateInstance($pagesDir, $typeSlug, $itemSlug)),
    ];
}