<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminPageAction;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Storage\PageData;

/**
 * @param Page $archetype
 * @param PageData[] $instances
 */
function render_archetype_instances(Page $archetype, array $instances): void
{
    ?>
    <h1><?= htmlspecialchars($archetype->label()) ?> instances</h1>
    <p>
        <a href="<?= AdminController::getArchetypeNewUrl($archetype->slug()) ?>">
            + New <?= htmlspecialchars($archetype->label()) ?>
        </a>
    </p>
    <?php if (empty($instances)): ?>
        <p>No instances yet.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <?php render_instance_row_header() ?>
            </thead>
            <tbody>
                <?php foreach ($instances as $instance): ?>
                    <?php render_instance_row(
                        $archetype,
                        $instance,
                        AdminController::getArchetypeEditUrl($archetype->slug(), $instance->id, AdminPageAction::EDIT),
                        AdminController::getArchetypeEditUrl($archetype->slug(), $instance->id, AdminPageAction::DELETE)
                    ); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}
