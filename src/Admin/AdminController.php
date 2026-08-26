<?php

namespace VanillaCms\Admin;

use Storage;
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
            case 'archetypes':
                if (sizeof($segments) === 1) {
                    self::renderArchetypes($segments[0] ?? null);
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
                <?php foreach (TypeRegistry::archetypePages() as $archetype): ?>
                    <p>
                        <a href="/admin/archetypes/<?= htmlspecialchars($archetype->slug()) ?>">
                            <?= htmlspecialchars($archetype->label()) ?>
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
            <?php foreach (TypeRegistry::simplePages() as $page): ?>
                <?php $pageData = Storage::findFirst($page->slug()); ?>
                <li>
                    <?= htmlspecialchars($page->label()) ?> (<?= htmlspecialchars($page->slug()) ?> | <?= $pageData?->id ?? 'no instance' ?>)
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    private static function renderArchetypes(?string $typeSlug): void
    {
        // If no archetype slug is provided, list all archetypes.
        if ($typeSlug === null) {
            ?>
            <h1>Archetypes</h1>
            <ul>
                <?php foreach (TypeRegistry::archetypePages() as $archetype): ?>
                    <li>
                        <a href="/admin/archetypes/<?= htmlspecialchars($archetype->slug()) ?>">
                            <?= htmlspecialchars($archetype->label()) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
            return;
        }

        // If we have an archetype slug, list all instances of that archetype.
        $archetype = TypeRegistry::getPage($typeSlug);

        if (!$archetype || !$archetype->isArchetype()) {
            Router::notFound();
            return;
        }
        
        $archetypeInstances = Storage::all($archetype->slug());
        ?>
        <h1><?= htmlspecialchars($archetype->label()) ?> instances</h1>
        <?php if (empty($archetypeInstances)): ?>
            <p>No instances yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($archetypeInstances as $pageInstance): ?>
                    <li>
                        <?= htmlspecialchars($pageInstance->name) ?> (<?= htmlspecialchars($pageInstance->slug) ?> | <?= $pageInstance?->id ?? 'no instance' ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php
    }
}
