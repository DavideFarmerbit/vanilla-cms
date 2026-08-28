<?php

namespace VanillaCms\Admin;

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
                $pageData ? self::getPageEditUrl($slug, AdminPageAction::DELETE) : null
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
            self::handleEditor($archetype, null, $backUrl, self::getArchetypeNewUrl($typeSlug), null);
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

    private static function handleEditor(Page $type, ?PageData $pageData, string $backUrl, string $saveAction, ?string $deleteAction): void
    {
        if (self::isVerifiedPost()) {
            $data = collect_page_editor_response($type);

            Storage::save($type->slug(), $pageData?->id, $data);
            Router::redirect($backUrl);
            return;
        }

        $instance = $pageData ? $type->instantiate($pageData) : null;
        render_page_editor($type, $instance, $backUrl, $saveAction, $deleteAction);
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
