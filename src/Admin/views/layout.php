<?php

use VanillaCms\Admin\AdminAssets;
use VanillaCms\Admin\AdminController;
use VanillaCms\Pages\PageTypeRegistry;

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

function vcms_render_tabs_links()
{
    ?>
    <?php vcms_nav_link(AdminController::getHomeUrl(), 'Home'); ?>
    <hr class="vcms-nav__separator">
    <?php vcms_nav_link(AdminController::getPagesUrl(), 'Pages'); ?>
    <?php foreach (PageTypeRegistry::archetypeTypes() as $archetype): ?>
        <?php vcms_nav_link(AdminController::getArchetypeUrl($archetype->slug()), $archetype->label()); ?>
    <?php endforeach; ?>
    <hr class="vcms-nav__separator">
    <?php vcms_nav_link(AdminController::getUploadsUrl(), 'Uploads'); ?>
    <?php vcms_nav_link(AdminController::getSharedFieldsUrl(), 'Shared Fields'); ?>
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
        'close' => '<path d="M4 4l8 8M12 4l-8 8"/>',
    ];
    ?>
    <svg class="vcms-icon" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $paths[$name] ?? '' ?></svg>
    <?php
}

/**
 * Month value => label pairs, shared by every year/month upload filter (the Uploads library and
 * the file field picker).
 * @return array<string, string>
 */
function vcms_months(): array
{
    return [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
    ];
}

/**
 * Renders the <template>s the file field picker (admin.js) clones: the picker dialog itself, and
 * a single upload grid item. Rendered once per admin page, cloned on demand.
 */
function render_admin_file_picker_templates(): void
{
    $currentYear = (int) date('Y');
    ?>
    <template data-vcms-file-picker-template>
        <dialog class="vcms-file-picker-dialog" tabindex="-1">
            <button type="button" class="vcms-icon-btn vcms-file-picker-dialog__close" data-vcms-file-picker-close title="Close" aria-label="Close">
                <?php vcms_icon('close') ?>
            </button>
            <div class="vcms-file-picker-dialog__filters">
                <span class="vcms-field__select-wrap">
                    <select class="vcms-field__input" data-vcms-file-picker-year>
                        <option value="">All years</option>
                        <?php for ($year = $currentYear; $year >= $currentYear - 5; $year--): ?>
                            <option value="<?= $year ?>"><?= $year ?></option>
                        <?php endfor; ?>
                    </select>
                </span>
                <span class="vcms-field__select-wrap">
                    <select class="vcms-field__input" data-vcms-file-picker-month>
                        <option value="">All months</option>
                        <?php foreach (vcms_months() as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </div>
            <div class="vcms-upload-grid vcms-file-picker-dialog__list" data-vcms-file-picker-list></div>
        </dialog>
    </template>
    <template data-vcms-file-picker-item-template>
        <button type="button" class="vcms-upload-grid__item" data-vcms-file-picker-item>
            <span class="vcms-upload-grid__name" data-vcms-file-picker-item-name></span>
        </button>
    </template>
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
            <div class="vcms-layout__sidebar-inner">
                <ul class="vcms-nav">
                    <?php vcms_render_tabs_links() ?>
                </ul>
            </div>
        </nav>
        <main class="vcms-layout__main">
            <div class="vcms-layout__main-inner">
    <?php
}

function render_admin_shell_close(): void
{
    ?>
        </div>
        </main>
    </div>
    <?php if (AdminAssets::jsUrl() !== ''): ?>
        <script type="module" src="<?= htmlspecialchars(AdminAssets::jsUrl()) ?>"></script>
    <?php endif; ?>
    </body>
    </html>
    <?php
}
