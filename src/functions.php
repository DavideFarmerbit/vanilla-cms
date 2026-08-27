<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Core\PageRenderer;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\RouterDispatcher;

/**
 * Registers a new page type.
 * @param class-string<Page> $pageClass
 */
function register_page(string $pageClass): void
{
    TypeRegistry::registerPage($pageClass);
}

/**
 * Registers an array of page type.
 * @param array<class-string<Page>> $pageClasses
 */
function register_pages(array $pageClasses): void
{
    array_map(fn ($pageClass) => TypeRegistry::registerPage($pageClass), $pageClasses);
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
            TypeRegistry::simplePages()
        ),
        // Archetypes
        ...array_map(
            fn ($archetype) => router_dispatcher(
                $archetype->slug() . '/{instance}',
                fn (string $instance) => PageRenderer::page($archetype->slug(), $instance)
            ),
            TypeRegistry::archetypePages()
        ),
        // Admin pannel
        AdminController::routerDispatcher(),
    ];
}