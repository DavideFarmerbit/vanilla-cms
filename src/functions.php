<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Core\PageRenderer;
use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\RouterDispatcher;

function DefinePage(string $slug, string $label, string $filePath, ?callable $urlBuilder = null): void
{
    TypeRegistry::registerPage($slug, $label, $filePath, $urlBuilder);
}

function DefinePageTemplate(string $slug, string $label, string $filePath, ?callable $urlBuilder = null): void
{
    TypeRegistry::registerTemplate($slug, $label, $filePath, $urlBuilder);
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
function default_router_dispatchers(): array {
    return [
        // Homepage
        router_dispatcher('', fn () => PageRenderer::page('home')),
        // Pages
        ...array_map(
            fn ($page) => router_dispatcher(
                $page->slug(),
                fn () => PageRenderer::page($page->slug())
            ),
            TypeRegistry::pages()
        ),
        // Templates
        ...array_map(
            fn ($template) => router_dispatcher(
                $template->slug() . '/{instance}',
                fn (string $instance) => PageRenderer::templateInstance($template->slug(), $instance)
            ),
            TypeRegistry::templates()
        ),
        // Admin pannel
        AdminController::routerDispatcher(),
    ];
}