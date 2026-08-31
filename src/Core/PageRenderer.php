<?php

namespace VanillaCms\Core;

use VanillaCms\Auth\Auth;
use VanillaCms\Pages\PageTypeRegistry;
use VanillaCms\Pages\PageVisibility;
use VanillaCms\Storage\PageData;
use VanillaCms\Storage\Storage;
use VanillaCms\Core\Router\Router;

final class PageRenderer
{
    /** Query param that must accompany an admin session to preview a HIDDEN page. */
    public const PREVIEW_PARAM = 'vcms_preview';

    public static function page(string $typeSlug, ?string $instanceSlug = null): void
    {
        $page = PageTypeRegistry::getPageType($typeSlug);

        if (!$page) {
            Router::notFound();
            return;
        }
        
        // Get the page data for the specified instance, or the first instance if none is specified.
        $pageData = $instanceSlug !== null ? Storage::findPageInstanceBySlug($typeSlug, $instanceSlug) : Storage::findFirstPageInstance($typeSlug);
        if (!$pageData) {
            Router::notFound();
            return;
        }
        
        $pageInstance = $page->instantiate($pageData);
        $visibility = $pageInstance->visibility();

        // Hidden pages only render for an admin who followed an explicit preview link never from public
        // navigation or a bare direct URL.
        $isAdminPreview = Auth::isAdmin() && ($_GET[self::PREVIEW_PARAM] ?? null) === '1';
        if ($visibility === PageVisibility::HIDDEN && !$isAdminPreview) {
            Router::notFound();
            return;
        }
        if ($visibility === PageVisibility::RESTRICTED && !Auth::isAdmin()) {
            Router::notFound();
            return;
        }
        
        $pageInstance->render();
    }
}
