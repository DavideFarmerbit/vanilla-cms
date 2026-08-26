<?php

namespace VanillaCms\Admin;

use PageData;
use Storage;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\Router;
use VanillaCms\Core\Router\RouterDispatcher;

require_once __DIR__ . '/views/layout.php';
require_once __DIR__ . '/views/pages_instances.php';
require_once __DIR__ . '/views/archetypes_list.php';
require_once __DIR__ . '/views/archetype_instances.php';
require_once __DIR__ . '/views/page_editor.php';

final class AdminController
{
    public static function routerDispatcher(): RouterDispatcher {
        return router_dispatcher('admin/{section}/*', fn (string $section, array $segments) => AdminController::dispatch($section, $segments));
    }

    public static function dispatch(string $section, array $segments): void
    {
        render_admin_shell_open();

        match ($section) {
            'pages' => self::dispatchPages($segments),
            'archetypes' => self::dispatchArchetypes($segments),
            default => Router::notFound(),
        };

        render_admin_shell_close();
    }

    private static function dispatchPages(array $segments): void
    {
        if (count($segments) === 0) {
            render_pages_instances(TypeRegistry::simplePages());
            return;
        }

        $slug = $segments[0];
        $action = $segments[1] ?? null;
        $page = TypeRegistry::getPage($slug);

        if (!$page || $page->isArchetype()) {
            Router::notFound();
            return;
        }

        $backUrl = '/admin/pages';

        if ($action === 'edit') {
            $instance = Storage::findFirst($page->slug());
            self::handleEditor(
                $page,
                $instance,
                $backUrl,
                "/admin/pages/{$slug}/edit",
                $instance ? "/admin/pages/{$slug}/delete" : null
            );
            return;
        }

        if ($action === 'delete' && self::isPost()) {
            $instance = Storage::findFirst($page->slug());
            if ($instance) {
                Storage::delete($page->slug(), $instance->id);
            }
            self::redirect($backUrl);
            return;
        }

        Router::notFound();
    }

    private static function dispatchArchetypes(array $segments): void
    {
        if (count($segments) === 0) {
            render_archetypes_list(TypeRegistry::archetypePages());
            return;
        }

        $typeSlug = $segments[0];
        $archetype = TypeRegistry::getPage($typeSlug);

        if (!$archetype || !$archetype->isArchetype()) {
            Router::notFound();
            return;
        }

        $backUrl = "/admin/archetypes/{$typeSlug}";

        if (count($segments) === 1) {
            render_archetype_instances($archetype, Storage::all($archetype->slug()));
            return;
        }

        $action = $segments[1];

        if ($action === 'new') {
            self::handleEditor($archetype, null, $backUrl, "/admin/archetypes/{$typeSlug}/new", null);
            return;
        }

        $id = $action;
        $subAction = $segments[2] ?? null;
        $instance = Storage::find($archetype->slug(), $id);

        if (!$instance) {
            Router::notFound();
            return;
        }

        if ($subAction === 'edit') {
            self::handleEditor(
                $archetype,
                $instance,
                $backUrl,
                "/admin/archetypes/{$typeSlug}/{$id}/edit",
                "/admin/archetypes/{$typeSlug}/{$id}/delete"
            );
            return;
        }

        if ($subAction === 'delete' && self::isPost()) {
            Storage::delete($archetype->slug(), $id);
            self::redirect($backUrl);
            return;
        }

        Router::notFound();
    }

    private static function handleEditor(Page $type, ?PageData $instance, string $backUrl, string $saveAction, ?string $deleteAction): void
    {
        if (self::isPost()) {
            $data = $instance ?? PageData::empty();
            $data->setPage($type);
            $data->slug = trim($_POST['slug'] ?? '');
            $data->name = trim($_POST['name'] ?? '');

            Storage::save($type->slug(), $instance?->id, $data);
            self::redirect($backUrl);
            return;
        }

        render_page_editor($type, $instance, $backUrl, $saveAction, $deleteAction);
    }

    private static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    private static function redirect(string $url): void
    {
        header('Location: ' . $url);
    }
}
