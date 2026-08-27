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
                <?php vcms_nav_link(AdminController::getPagesUrl(), 'Pages'); ?>
                <?php foreach (TypeRegistry::archetypePages() as $archetype): ?>
                    <?php vcms_nav_link(AdminController::getArchetypeUrl($archetype->slug()), $archetype->label()); ?>
                <?php endforeach; ?>
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
        <h1>Vanilla Cms</h1>
        <p>Select a category from the side bar to get started.</p>
    <?php
}