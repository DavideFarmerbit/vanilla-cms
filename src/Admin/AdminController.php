<?php

namespace VanillaCms\Admin;

use Closure;
use VanillaCms\Auth\Auth;
use VanillaCms\Auth\Csrf;
use VanillaCms\Pages\Page;
use VanillaCms\Pages\PageTypeRegistry;
use VanillaCms\Core\Router\Router;
use VanillaCms\Core\Router\RouterDispatcher;
use VanillaCms\Storage\PageData;
use VanillaCms\Storage\Storage;
use VanillaCms\Storage\UploadData;
use VanillaCms\Uploads\UploadMeta;
use VanillaCms\Uploads\UploadTypeRegistry;

require_once __DIR__ . '/views/layout.php';
require_once __DIR__ . '/views/instance_row.php';
require_once __DIR__ . '/views/pages_instances.php';
require_once __DIR__ . '/views/archetypes_list.php';
require_once __DIR__ . '/views/archetype_instances.php';
require_once __DIR__ . '/views/page_editor.php';
require_once __DIR__ . '/views/uploads_library.php';
require_once __DIR__ . '/views/upload_editor.php';

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

    public static function getUploadsUrl(): string {
        return "/admin/uploads";
    }

    /**
     * @param string[] $batch ids of the uploads to move prev/next between; omitted when editing outside a batch.
     */
    public static function getUploadEditUrl(string $id, array $batch = []): string {
        $url = "/admin/uploads/{$id}/edit";
        if (!empty($batch)) {
            $url .= '?' . http_build_query(['batch' => implode(',', $batch)]);
        }
        return $url;
    }

    public static function getUploadDeleteUrl(string $id): string {
        return "/admin/uploads/{$id}/delete";
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
            Router::redirectWithReturn(Auth::unauthorizedUrl());
        }
        
        render_admin_shell_open();

        $section = $segments[0] ?? '';
        $trailingSegments = array_slice($segments, 1);
        match ($section) {
            '' => Router::redirect(self::getHomeUrl()),
            'home' => self::dispatchHome(),
            'pages' => self::dispatchPages($trailingSegments),
            'archetypes' => self::dispatchArchetypes($trailingSegments),
            'uploads' => self::dispatchUploads($trailingSegments),
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
            $pageData = Storage::findFirstPageInstance($page->slug());
            if ($pageData) {
                Storage::deletePageInstance($page->slug(), $pageData->id);
            }
            Router::redirect($backUrl);
            return;
        }

        Router::notFound();
    }

    private static function dispatchArchetypes(array $segments): void
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
        $pageData = Storage::findPageInstance($archetype->slug(), $id);

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
            Storage::deletePageInstance($archetype->slug(), $id);
            Router::redirect($backUrl);
            return;
        }

        Router::notFound();
    }
    
    private static function dispatchUploads(array $segments): void
    {
        if (count($segments) === 0) {
            if (self::isVerifiedPost()) {
                self::handleUpload();
                return;
            }
            self::renderUploadsLibrary();
            return;
        }

        $id = $segments[0];
        $subAction = $segments[1] ?? null;
        $uploadData = Storage::findUpload($id);

        if (!$uploadData) {
            Router::notFound();
            return;
        }

        if ($subAction === 'edit') {
            self::handleUploadEditor($uploadData);
            return;
        }

        if ($subAction === 'delete' && self::isVerifiedPost()) {
            Storage::deleteUpload($id);
            Router::redirect(self::getUploadsUrl());
            return;
        }

        Router::notFound();
    }

    private static function renderUploadsLibrary(): void
    {
        $uploads = array_map(fn (UploadData $data) => UploadMeta::instantiate($data), Storage::allUploads());

        $typeFilter = $_GET['type'] ?? '';
        $yearFilter = $_GET['year'] ?? '';
        $monthFilter = $_GET['month'] ?? '';

        $filtered = array_values(array_filter($uploads, function (UploadMeta $upload) use ($typeFilter, $yearFilter, $monthFilter) {
            if ($typeFilter !== '' && $upload->type() !== $typeFilter) {
                return false;
            }
            if ($yearFilter !== '' && date('Y', $upload->uploadedAt()) !== $yearFilter) {
                return false;
            }
            if ($monthFilter !== '' && date('m', $upload->uploadedAt()) !== $monthFilter) {
                return false;
            }
            return true;
        }));

        usort($filtered, fn (UploadMeta $a, UploadMeta $b) => $b->uploadedAt() <=> $a->uploadedAt());

        render_uploads_library($filtered, $uploads, self::getUploadsUrl(), $typeFilter, $yearFilter, $monthFilter);
    }

    private static function handleUpload(): void
    {
        $ids = [];
        $files = $_FILES['files'] ?? null;

        for ($i = 0; $files && $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['name'][$i] === '') {
                continue;
            }

            $originalName = $files['name'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!UploadTypeRegistry::isExtensionAllowed($extension)) {
                continue;
            }

            $mimeType = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $files['tmp_name'][$i]) ?: '';
                finfo_close($finfo);
            }

            $id = Storage::newId();
            $path = Storage::storeUploadedFile($files['tmp_name'][$i], $id, $extension);

            $data = UploadData::empty();
            $data->type = UploadTypeRegistry::typeForExtension($extension);
            $data->name = pathinfo($originalName, PATHINFO_FILENAME);
            $data->path = $path;
            $data->originalName = $originalName;
            $data->mimeType = $mimeType;
            $data->size = $files['size'][$i];
            $data->uploadedAt = time();

            Storage::saveUpload($id, $data);
            $ids[] = $id;
        }

        if (empty($ids)) {
            Router::redirect(self::getUploadsUrl());
            return;
        }

        Router::redirect(self::getUploadEditUrl($ids[0], $ids));
    }

    private static function handleUploadEditor(UploadData $uploadData): void
    {
        $batch = isset($_GET['batch']) ? array_values(array_filter(explode(',', $_GET['batch']))) : [];

        if (self::isVerifiedPost()) {
            $uploadData->name = trim($_POST['name'] ?? '');
            $uploadData->fields = $_POST['fields'] ?? [];

            Storage::saveUpload($uploadData->id, $uploadData);
            Router::redirect(self::getUploadEditUrl($uploadData->id, $batch));
            return;
        }

        $meta = UploadMeta::instantiate($uploadData);
        render_upload_editor(
            $meta,
            self::getUploadsUrl(),
            self::getUploadEditUrl($meta->id(), $batch),
            self::getUploadDeleteUrl($meta->id()),
            $batch
        );
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

            $id = Storage::savePageInstance($type->slug(), $pageData?->id, $data);
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
