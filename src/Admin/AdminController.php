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
    /** @var array<string, AdminTab[]> category slug => tab instance array */
    private static array $tabsRegistry;
    
    public static function routerDispatcher(): RouterDispatcher {
        return router_dispatcher('admin/*', fn (array $segments) => AdminController::dispatch($segments));
    }

    public static function dispatch(array $segments): void
    {
        if (!Auth::isAdmin()) {
            Router::redirectWithReturn(Auth::unauthorizedUrl());
        }
        
        // Handle API requests first, so that the admin shell is not rendered.
        $requestHandled = self::foreachTab(fn(string $categorySlug, AdminTab $tab) => $tab->handleApiRequest($segments));
        if ($requestHandled) {
            return;
        }

        // If it wasn't an API request, render the admin shell.
        render_admin_shell_open();

        $section = $segments[0] ?? '';
        $trailingSegments = array_slice($segments, 1);
        $tabDispatched = self::foreachTab(function (string $categorySlug, AdminTab $tab) use ($section, $trailingSegments) {
            if ($tab->slug() !== $section) {
                return false;
            }
            
            $tab->dispatch($trailingSegments);
            return true;
        });
        if (!$tabDispatched) {
            Router::notFound();
        }

        render_admin_shell_close();
    }
    
    /** 
     * Calls the callback for each tab in the registry. Returning true in the callback will stop the iteration.
     * @param Closure(string $categorySlug, AdminTab $tab): bool $callback
     * @return true if the iteration was stopped early, false otherwise.
     */
    protected static function foreachTab(Closure $callback): bool {
        foreach (self::$tabsRegistry as $categorySlug => $tabs) {
            foreach ($tabs as $tab) {
                if ($callback($categorySlug, $tab)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param Closure(string $id): string $editUrlBuilder Builds the editor url to land on after a successful save,
     *                                                     given the (possibly newly generated) instance id.
     */
    public static function handleEditor(Page $type, ?PageData $pageData, string $backUrl, string $saveAction, ?string $deleteAction, Closure $editUrlBuilder): void
    {
        if (self::isVerifiedPost()) {
            $data = collect_page_editor_response($type);

            $id = Storage::savePageInstance($type->slug(), $pageData?->id, $data);
            Router::redirect($editUrlBuilder($id));
        }

        // Instantiate from existing data or default instance.
        $instance = $type->instantiate($pageData ?? $type->toPageData());
        render_page_editor($instance, $backUrl, $saveAction, $deleteAction, $pageData === null);
    }

    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function isVerifiedPost(): bool
    {
        return self::isPost() && Csrf::verify($_POST['csrf_token'] ?? null);
    }
}
