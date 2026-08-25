<?php

namespace VanillaCms\Admin;

use VanillaCms\Core\TypeRegistry;

final class AdminController
{
    public static function dispatch(array $segments): void
    {
        $section = $segments[0] ?? 'pages';

        self::renderShellOpen();

        switch ($section) {
            case 'pages':
                self::renderPages();
                break;
            case 'templates':
                self::renderTemplates($segments[1] ?? null);
                break;
            default:
                http_response_code(404);
                echo '<p>Not found.</p>';
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
                        <a href="/admin/templates/<?= htmlspecialchars($template['slug']) ?>">
                            <?= htmlspecialchars($template['label']) ?>
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
                <li><?= htmlspecialchars($page['label']) ?> (<?= htmlspecialchars($page['slug']) ?>)</li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    private static function renderTemplates(?string $slug): void
    {
        if ($slug === null) {
            ?>
            <h1>Templates</h1>
            <ul>
                <?php foreach (TypeRegistry::templates() as $template): ?>
                    <li>
                        <a href="/admin/templates/<?= htmlspecialchars($template['slug']) ?>">
                            <?= htmlspecialchars($template['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
            return;
        }

        $template = TypeRegistry::getTemplate($slug);

        if (!$template) {
            http_response_code(404);
            echo '<p>Unknown template type.</p>';
            return;
        }
        ?>
        <h1><?= htmlspecialchars($template['label']) ?> instances</h1>
        <p>No storage layer yet, so there is nothing to list here.</p>
        <?php
    }
}
