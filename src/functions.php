<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminTagGroup;
use VanillaCms\Admin\Tabs\ArchetypeTab;
use VanillaCms\Admin\Tabs\HomeTab;
use VanillaCms\Admin\Tabs\PagesTab;
use VanillaCms\Admin\Tabs\SharedFieldsTab;
use VanillaCms\Admin\Tabs\UploadsTab;
use VanillaCms\Core\PageRenderer;
use VanillaCms\Pages\Page;
use VanillaCms\Pages\PageTypeRegistry;
use VanillaCms\Core\Router\RouterDispatcher;
use VanillaCms\Storage\Storage;
use VanillaCms\Uploads\UploadMeta;
use VanillaCms\Uploads\UploadTypeRegistry;

/**
 * Registers a new page type.
 * @param class-string<Page> $pageClass
 */
function register_page(string $pageClass): void
{
    PageTypeRegistry::registerType($pageClass);
}

/**
 * Registers an array of page type.
 * @param array<class-string<Page>> $pageClasses
 */
function register_pages(array $pageClasses): void
{
    array_map(fn ($pageClass) => PageTypeRegistry::registerType($pageClass), $pageClasses);
}

/**
 * Registers a new upload type, associating it to a set of file extensions.
 * @param string $key unique key identifying the type (e.g. 'image').
 * @param class-string<UploadMeta> $uploadMetaClass
 * @param string[] $extensions file extensions (without the leading dot) handled by this type; also allow-listed.
 */
function register_upload_type(string $key, string $uploadMetaClass, array $extensions): void
{
    UploadTypeRegistry::registerType($key, $uploadMetaClass, $extensions);
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
            PageTypeRegistry::simpleTypes()
        ),
        // Archetypes
        ...array_map(
            fn ($archetype) => router_dispatcher(
                $archetype->slug() . '/{instance}',
                fn (string $instance) => PageRenderer::page($archetype->slug(), $instance)
            ),
            PageTypeRegistry::archetypeTypes()
        ),
        // Admin pannel
        AdminController::routerDispatcher(),
    ];
}

function vcms_register_default_admin_tabs(): void
{
    AdminController::registerGroup(new AdminTagGroup('vanilla-cms', 'Vanilla CMS'));
    AdminController::registerGroup(new AdminTagGroup('pages', 'Pages'));
    AdminController::registerGroup(new AdminTagGroup('content', 'Content'));
    AdminController::registerTab('vanilla-cms', new HomeTab());
    AdminController::registerTab('pages', new PagesTab());
    foreach (PageTypeRegistry::archetypeTypes() as $type) {
        AdminController::registerTab('pages', new ArchetypeTab($type->slug(), $type->label()));
    }
    AdminController::registerTab('content', new UploadsTab());
    AdminController::registerTab('content', new SharedFieldsTab());
}

/**
 * Returns the first instance of a given page type.
 * @return Page|null
 */
function get_first_page_instance_by_type(string $slug): ?Page {
    $type = PageTypeRegistry::getPageType($slug);
    $pageDataArray = Storage::findFirstPageInstance($type->slug());
    return $pageDataArray !== null ? $type->instantiate($pageDataArray) : null;
}

/** 
 * Returns all page instances of a given type.
 * @return Page[]
 */
function get_page_instances_by_type(string $slug): array {
    $type = PageTypeRegistry::getPageType($slug);
    $pageDataArray = Storage::allPageInstances($type->slug());
    return array_map(fn($pageData) => $type->instantiate($pageData), $pageDataArray);
}