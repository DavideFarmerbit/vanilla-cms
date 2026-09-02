<?php

namespace VanillaCms\Admin\Tabs;

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminPageAction;
use VanillaCms\Admin\AdminTab;
use VanillaCms\Core\Router\Router;
use VanillaCms\Pages\PageTypeRegistry;
use VanillaCms\Storage\Storage;

class ArchetypeTab extends AdminTab
{
    public function __construct(string $slug, string $label)
    {
        parent::__construct($slug, $label);
    }

    public static function getArchetypesUrl(): string {
        return '/admin/archetypes';
    }

    public static function getArchetypeUrl(string $slug): string {
        return "/admin/archetypes/{$slug}";
    }

    public static function getArchetypeEditUrl(string $slug, string $id, AdminPageAction $action): string {
        $actionString = strtolower($action->name);
        return "/admin/archetypes/{$slug}/{$id}/{$actionString}";
    }

    public static function getArchetypeNewUrl(string $slug): string {
        return "/admin/archetypes/{$slug}/new";
    }

    public function dispatch(array $segments): void
    {
        if (count($segments) === 0) {
            render_archetypes_list(PageTypeRegistry::archetypeTypes());
            return;
        }

        $typeSlug = $segments[0];
        $archetype = PageTypeRegistry::getPageType($typeSlug);

        if (!$archetype || !$archetype->isArchetype()) {
            Router::notFound();
            return;
        }

        $backUrl = self::getArchetypeUrl($typeSlug);

        if (count($segments) === 1) {
            render_archetype_instances($archetype, Storage::allPageInstances($archetype->slug()));
            return;
        }

        $action = $segments[1];

        if ($action === 'new') {
            AdminController::handleEditor(
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
        $pageData = Storage::findPageInstance($archetype->slug(), $id);

        if (!$pageData) {
            Router::notFound();
            return;
        }

        if ($subAction === 'edit') {
            AdminController::handleEditor(
                $archetype,
                $pageData,
                $backUrl,
                self::getArchetypeEditUrl($typeSlug, $id, AdminPageAction::EDIT),
                self::getArchetypeEditUrl($typeSlug, $id, AdminPageAction::DELETE),
                fn (string $newId) => self::getArchetypeEditUrl($typeSlug, $newId, AdminPageAction::EDIT)
            );
            return;
        }

        if ($subAction === 'delete' && AdminController::isVerifiedPost()) {
            Storage::deletePageInstance($archetype->slug(), $id);
            Router::redirect($backUrl);
        }

        Router::notFound();
    }
}