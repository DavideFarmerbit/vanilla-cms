<?php

namespace VanillaCms\Admin\Tabs;

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminPageAction;
use VanillaCms\Admin\AdminTab;
use VanillaCms\Core\Router\Router;
use VanillaCms\Pages\PageTypeRegistry;
use VanillaCms\Storage\Storage;

class PagesTab extends AdminTab
{
    public function __construct()
    {
        parent::__construct('pages', 'Pages');
    }

    public static function getPagesUrl(): string
    {
        return '/admin/pages';
    }

    public static function getPageEditUrl(string $slug, AdminPageAction $action): string {
        $actionString = strtolower($action->name);
        return "/admin/pages/{$slug}/{$actionString}";
    }

    public function dispatch(array $segments): void
    {
        if (count($segments) === 0) {
            render_pages_instances(PageTypeRegistry::simpleTypes());
            return;
        }

        $slug = $segments[0];
        $action = $segments[1] ?? null;
        $page = PageTypeRegistry::getPageType($slug);

        if (!$page || $page->isArchetype()) {
            Router::notFound();
            return;
        }

        $backUrl = self::getPagesUrl();

        if ($action === 'edit') {
            $pageData = Storage::findFirstPageInstance($page->slug());
            AdminController::handleEditor(
                $page,
                $pageData,
                $backUrl,
                self::getPageEditUrl($slug, AdminPageAction::EDIT),
                $pageData ? self::getPageEditUrl($slug, AdminPageAction::DELETE) : null,
                fn (string $id) => self::getPageEditUrl($slug, AdminPageAction::EDIT)
            );
            return;
        }

        if ($action === 'delete' && AdminController::isVerifiedPost()) {
            $pageData = Storage::findFirstPageInstance($page->slug());
            if ($pageData) {
                Storage::deletePageInstance($page->slug(), $pageData->id);
            }
            Router::redirect($backUrl);
        }

        Router::notFound();
    }
}