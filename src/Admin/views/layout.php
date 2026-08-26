<?php

use VanillaCms\Admin\AdminAssets;
use VanillaCms\Core\Registry\TypeRegistry;

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
    <div class="admin-layout">
        <nav class="admin-sidebar">
            <p><a href="/admin/pages">Pages</a></p>
            <?php foreach (TypeRegistry::archetypePages() as $archetype): ?>
                <p>
                    <a href="/admin/archetypes/<?= htmlspecialchars($archetype->slug()) ?>">
                        <?= htmlspecialchars($archetype->label()) ?>
                    </a>
                </p>
            <?php endforeach; ?>
        </nav>
        <main class="admin-main">
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
