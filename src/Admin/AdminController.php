<?php

namespace VanillaCms\Admin;

use Closure;
use VanillaCms\Auth\Auth;
use VanillaCms\Auth\Csrf;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\Router;
use VanillaCms\Core\Router\RouterDispatcher;
use VanillaCms\Storage\PageData;
use VanillaCms\Storage\Storage;

require_once __DIR__ . '/views/layout.php';
require_once __DIR__ . '/views/instance_row.php';
require_once __DIR__ . '/views/pages_instances.php';
require_once __DIR__ . '/views/archetypes_list.php';
require_once __DIR__ . '/views/archetype_instances.php';
require_once __DIR__ . '/views/page_editor.php';

final class AdminController
{
    public static function routerDispatcher(): RouterDispatcher {
        return router_dispatcher('admin/*', fn (array $segments) => AdminController::dispatch($segments));
    }
    
    public static function getHomeUrl(): string 
    {
        return '/admin/home';
    }
    
    public static function getPagesUrl(): string 
    {
        return '/admin/pages';
    }
    
    public static function getArchetypesUrl(): string {
        return '/admin/archetypes';
    }

    public static function getArchetypeUrl(string $slug): string {
        return "/admin/archetypes/{$slug}";
    }
    
    public static function getPageEditUrl(string $slug, AdminPageAction $action): string {
        $actionString = strtolower($action->name);
        return "/admin/pages/{$slug}/{$actionString}";
    }
    
    public static function getArchetypeEditUrl(string $slug, string $id, AdminPageAction $action): string {
        $actionString = strtolower($action->name);
        return "/admin/archetypes/{$slug}/{$id}/{$actionString}";
    }

    public static function getArchetypeNewUrl(string $slug): string {
        return "/admin/archetypes/{$slug}/new";
    }

    public static function getSharedFieldsUrl(): string {
        return "/admin/shared-fields";
    }

    public static function getSharedFieldUrl(string $slug): string {
        return "/admin/shared-fields/{$slug}";
    }

    public static function dispatch(array $segments): void
    {
        if (!Auth::isAdmin()) {
            // TODO: keep context of the current page we want to visit. We will want to redirect to that page after
            //       login, or if refreshing the page after we logged in from another tab.
            Router::redirect(Auth::unauthorizedUrl());
        }
        
        render_admin_shell_open();

        $section = $segments[0] ?? '';
        $trailingSegments = array_slice($segments, 1);
        match ($section) {
            '' => Router::redirect(self::getHomeUrl()),
            'home' => self::dispatchHome(),
            'pages' => self::dispatchPages($trailingSegments),
            'archetypes' => self::dispatchArchetypes($trailingSegments),
            'shared-fields' => self::dispatchSharedFields($trailingSegments),
            default => Router::notFound(),
        };

        render_admin_shell_close();
    }
    
    private static function dispatchHome(): void
    {
        render_admin_home();
    }

    private static function dispatchPages(array $segments): void
    {
        if (count($segments) === 0) {
            render_pages_instances(TypeRegistry::simplePageTypes());
            return;
        }

        $slug = $segments[0];
        $action = $segments[1] ?? null;
        $page = TypeRegistry::getPageType($slug);

        if (!$page || $page->isArchetype()) {
            Router::notFound();
            return;
        }

        $backUrl = self::getPagesUrl();

        if ($action === 'edit') {
            $pageData = Storage::findFirst($page->slug());
            self::handleEditor(
                $page,
                $pageData,
                $backUrl,
                self::getPageEditUrl($slug, AdminPageAction::EDIT),
                $pageData ? self::getPageEditUrl($slug, AdminPageAction::DELETE) : null,
                fn (string $id) => self::getPageEditUrl($slug, AdminPageAction::EDIT)
            );
            return;
        }

        if ($action === 'delete' && self::isVerifiedPost()) {
            $pageData = Storage::findFirst($page->slug());
            if ($pageData) {
                Storage::delete($page->slug(), $pageData->id);
            }
            Router::redirect($backUrl);
            return;
        }

        Router::notFound();
    }

    private static function dispatchArchetypes(array $segments): void
    {
        if (count($segments) === 0) {
            render_archetypes_list(TypeRegistry::archetypePageTypes());
            return;
        }

        $typeSlug = $segments[0];
        $archetype = TypeRegistry::getPageType($typeSlug);

        if (!$archetype || !$archetype->isArchetype()) {
            Router::notFound();
            return;
        }

        $backUrl = self::getArchetypeUrl($typeSlug);

        if (count($segments) === 1) {
            render_archetype_instances($archetype, Storage::all($archetype->slug()));
            return;
        }

        $action = $segments[1];

        if ($action === 'new') {
            self::handleEditor(
                $archetype,
                null,
                $backUrl,
                self::getArchetypeNewUrl($typeSlug),
                null,
                fn (string $id) => self::getArchetypeEditUrl($typeSlug, $id, AdminPageAction::EDIT)
            );
            return;
        }

        $id = $action;
        $subAction = $segments[2] ?? null;
        $pageData = Storage::find($archetype->slug(), $id);

        if (!$pageData) {
            Router::notFound();
            return;
        }

        if ($subAction === 'edit') {
            self::handleEditor(
                $archetype,
                $pageData,
                $backUrl,
                self::getArchetypeEditUrl($typeSlug, $id, AdminPageAction::EDIT),
                self::getArchetypeEditUrl($typeSlug, $id, AdminPageAction::DELETE),
                fn (string $newId) => self::getArchetypeEditUrl($typeSlug, $newId, AdminPageAction::EDIT)
            );
            return;
        }

        if ($subAction === 'delete' && self::isVerifiedPost()) {
            Storage::delete($archetype->slug(), $id);
            Router::redirect($backUrl);
            return;
        }

        Router::notFound();
    }
    
    private static function dispatchSharedFields(array $segments): void
    {
        Router::notFound();
    }

    /**
     * @param Closure(string $id): string $editUrlBuilder Builds the editor url to land on after a successful save,
     *                                                     given the (possibly newly generated) instance id.
     */
    private static function handleEditor(Page $type, ?PageData $pageData, string $backUrl, string $saveAction, ?string $deleteAction, Closure $editUrlBuilder): void
    {
        if (self::isVerifiedPost()) {
            $data = collect_page_editor_response($type);

            $id = Storage::save($type->slug(), $pageData?->id, $data);
            Router::redirect($editUrlBuilder($id));
            return;
        }

        // Instantiate from existing data or default instance.
        $instance = $type->instantiate($pageData ?? $type->toPageData());
        render_page_editor($instance, $backUrl, $saveAction, $deleteAction, $pageData === null);
    }

    private static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    private static function isVerifiedPost(): bool
    {
        return self::isPost() && Csrf::verify($_POST['csrf_token'] ?? null);
    }
}
