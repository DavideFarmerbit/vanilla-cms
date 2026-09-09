<?php

namespace VanillaCms\Admin\Tabs;

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminPageAction;
use VanillaCms\Admin\AdminTab;
use VanillaCms\Core\Router\Router;
use VanillaCms\Pages\Page;
use VanillaCms\Pages\PageTypeRegistry;
use VanillaCms\Storage\PageData;
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
    
    public function fullSlug(): string
    {
        return 'archetypes/'. $this->slug();
    }

    public function handleApiRequest(array $segments): bool
    {
        if (count($segments) === 0) {
            return false;
        }

        $archetype = PageTypeRegistry::getPageType($this->slug());
        if (!$archetype || !$archetype->isArchetype()) {
            return false;
        }

        $action = $segments[0];

        if ($action === 'new' && AdminController::isVerifiedPost()) {
            return AdminController::handleEditorSave(
                $archetype,
                null,
                fn (string $id) => self::getArchetypeEditUrl($this->slug(), $id, AdminPageAction::EDIT)
            );
        }

        $id = $action;
        $subAction = $segments[1] ?? null;
        $pageData = Storage::findPageInstance($archetype->slug(), $id);

        if (!$pageData) {
            return false;
        }

        if ($subAction === 'edit' && AdminController::isVerifiedPost()) {
            return AdminController::handleEditorSave(
                $archetype,
                $pageData,
                fn (string $newId) => self::getArchetypeEditUrl($this->slug(), $newId, AdminPageAction::EDIT)
            );
        }

        if ($subAction === 'delete' && AdminController::isVerifiedPost()) {
            Storage::deletePageInstance($archetype->slug(), $id);
            Router::redirect(self::getArchetypeUrl($this->slug()));
        }

        return false;
    }

    public function dispatch(array $segments): void
    {
        $archetype = PageTypeRegistry::getPageType($this->slug());

        if (!$archetype || !$archetype->isArchetype()) {
            AdminController::notFound();
            return;
        }

        $backUrl = self::getArchetypeUrl($this->slug());

        if (count($segments) === 0) {
            $this->renderArchetypeInstances($archetype, Storage::allPageInstances($archetype->slug()));
            return;
        }

        $action = $segments[0];

        if ($action === 'new') {
            AdminController::renderEditor($archetype, null, $backUrl, self::getArchetypeNewUrl($this->slug()), null);
            return;
        }

        $id = $action;
        $subAction = $segments[1] ?? null;
        $pageData = Storage::findPageInstance($archetype->slug(), $id);

        if (!$pageData) {
            AdminController::notFound();
            return;
        }

        if ($subAction === 'edit') {
            AdminController::renderEditor(
                $archetype,
                $pageData,
                $backUrl,
                self::getArchetypeEditUrl($this->slug(), $id, AdminPageAction::EDIT),
                self::getArchetypeEditUrl($this->slug(), $id, AdminPageAction::DELETE)
            );
            return;
        }

        AdminController::notFound();
    }

    /**
     * @param Page $archetype
     * @param PageData[] $instances
     */
    protected function renderArchetypeInstances(Page $archetype, array $instances): void
    {
        ?>
        <h1 class="vcms-page-title"><?= htmlspecialchars($archetype->label()) ?> instances</h1>
        <p>
            <a class="vcms-btn vcms-btn--primary" href="<?= htmlspecialchars(self::getArchetypeNewUrl($archetype->slug())) ?>">
                + New <?= htmlspecialchars($archetype->label()) ?>
            </a>
        </p>
        <?php if (empty($instances)): ?>
            <p class="vcms-empty-state">No instances yet.</p>
        <?php else: ?>
            <table class="vcms-table">
                <thead>
                <?php vcms_render_instance_row_header() ?>
                </thead>
                <tbody>
                <?php foreach ($instances as $instance): ?>
                    <?php vcms_render_instance_row(
                        $archetype,
                        $instance,
                        self::getArchetypeEditUrl($archetype->slug(), $instance->id, AdminPageAction::EDIT),
                        self::getArchetypeEditUrl($archetype->slug(), $instance->id, AdminPageAction::DELETE)
                    ); ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php
    }

    /** @param Page[] $archetypes */
    protected function renderArchetypesList(array $archetypes): void
    {
        ?>
        <h1 class="vcms-page-title">Archetypes</h1>
        <ul class="vcms-list">
            <?php foreach ($archetypes as $archetype): ?>
                <li class="vcms-list__item">
                    <a class="vcms-list__link" href="<?= htmlspecialchars(self::getArchetypeUrl($archetype->slug())) ?>">
                        <?= htmlspecialchars($archetype->label()) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

}