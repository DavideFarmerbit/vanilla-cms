<?php

namespace VanillaCms\Admin;

use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\Router;
use VanillaCms\Core\Router\RouterDispatcher;

final class AdminController
{
    public static function routerDispatcher(): RouterDispatcher {
        return router_dispatcher('admin/{section}/*', fn (string $section, array $segments) => AdminController::dispatch($section, $segments));
    }
    
    public static function dispatch(string $section, array $segments): void
    {
        self::renderShellOpen();

        switch ($section) {
            case 'pages':
                self::renderPages();
                break;
            case 'templates':
                if (sizeof($segments) === 1) {
                    self::renderTemplates($segments[0] ?? null);
                } else {
                    Router::notFound();
                }
                break;
            default:
                Router::notFound();
        }

        self::renderShellClose();
    }

    private static function renderShellOpen(): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Admin</title>
        </head>
        <body>
        <div style="display:flex">
            <nav style="width:200px">
                <p><a href="/admin/pages">Pages</a></p>
                <?php foreach (TypeRegistry::templates() as $template): ?>
                    <p>
                        <a href="/admin/templates/<?= htmlspecialchars($template->slug()) ?>">
                            <?= htmlspecialchars($template->label()) ?>
                        </a>
                    </p>
                <?php endforeach; ?>
            </nav>
            <main>
        <?php
    }

    private static function renderShellClose(): void
    {
        ?>
            </main>
        </div>
        </body>
        </html>
        <?php
    }

    private static function renderPages(): void
    {
        ?>
        <h1>Pages</h1>
        <ul>
            <?php foreach (TypeRegistry::pages() as $page): ?>
                <li><?= htmlspecialchars($page->label()) ?> (<?= htmlspecialchars($page->slug()) ?>)</li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    private static function renderTemplates(?string $typeSlug): void
    {
        if ($typeSlug === null) {
            ?>
            <h1>Templates</h1>
            <ul>
                <?php foreach (TypeRegistry::templates() as $template): ?>
                    <li>
                        <a href="/admin/templates/<?= htmlspecialchars($template->slug()) ?>">
                            <?= htmlspecialchars($template->label()) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
            return;
        }

        $template = TypeRegistry::getTemplate($typeSlug);

        if (!$template) {
            Router::notFound();
            return;
        }
        ?>
        <h1><?= htmlspecialchars($template->label()) ?> instances</h1>
        <p>No storage layer yet, so there is nothing to list here.</p>
        <?php
    }
}
