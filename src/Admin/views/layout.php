<?php

use VanillaCms\Admin\AdminAssets;
use VanillaCms\Admin\AdminController;
use VanillaCms\Core\Registry\TypeRegistry;

function vcms_is_active_nav_link(string $url): bool
{
    $current = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
    $target = rtrim($url, '/');

    return $current === $target || str_starts_with($current, $target . '/');
}

function vcms_nav_link(string $url, string $label): void
{
    $activeClass = vcms_is_active_nav_link($url) ? ' vcms-nav__link--active' : '';
    ?>
    <li class="vcms-nav__item">
        <a class="vcms-nav__link<?= $activeClass ?>" href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a>
    </li>
    <?php
}

/**
 * Renders one of the shared inline icons (used by .vcms-icon-btn buttons).
 * Add new icons here as new row/toolbar actions are introduced.
 */
function vcms_icon(string $name): void
{
    $paths = [
        'edit' => '<path d="M10.5 2.5l3 3L5 14H2v-3l8.5-8.5Z"/><path d="M9 4l3 3"/>',
        'add' => '<path d="M8 3v10M3 8h10"/>',
        'trash' => '<path d="M3 4.5h10"/><path d="M6 4.5V3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1.5"/><path d="M4.5 4.5 5 13a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1l.5-8.5"/><path d="M6.5 7v4M9.5 7v4"/>',
        'back' => '<path d="M9.5 3.5 5 8l4.5 4.5"/>',
    ];
    ?>
    <svg class="vcms-icon" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $paths[$name] ?? '' ?></svg>
    <?php
}

function render_admin_shell_open(): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Admin</title>
        <?php if (AdminAssets::cssUrl() !== ''): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars(AdminAssets::cssUrl()) ?>">
        <?php endif; ?>
    </head>
    <body>
    <div class="vcms-layout">
        <nav class="vcms-layout__sidebar">
            <ul class="vcms-nav">
                <?php vcms_nav_link(AdminController::getHomeUrl(), 'Home'); ?>
                <hr class="vcms-nav__separator">
                <?php vcms_nav_link(AdminController::getPagesUrl(), 'Pages'); ?>
                <?php foreach (TypeRegistry::archetypePageTypes() as $archetype): ?>
                    <?php vcms_nav_link(AdminController::getArchetypeUrl($archetype->slug()), $archetype->label()); ?>
                <?php endforeach; ?>
                <hr class="vcms-nav__separator">
                <?php vcms_nav_link(AdminController::getUploadsUrl(), 'Uploads'); ?>
                <?php vcms_nav_link(AdminController::getSharedFieldsUrl(), 'Shared Fields'); ?>
            </ul>
        </nav>
        <main class="vcms-layout__main">
    <?php
}

function render_admin_shell_close(): void
{
    ?>
        </main>
    </div>
    <?php if (AdminAssets::jsUrl() !== ''): ?>
        <script type="module" src="<?= htmlspecialchars(AdminAssets::jsUrl()) ?>"></script>
    <?php endif; ?>
    </body>
    </html>
    <?php
}

function render_admin_home(): void {
    ?>
        <h1 class="vcms-page-title">Vanilla Cms</h1>
        <p>Select a category from the side bar to get started.</p>
    <?php
}