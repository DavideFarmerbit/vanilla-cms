<?php

use VanillaCms\Core\Registry\Page;

/**
 * @param Page $archetype
 * @param PageData[] $instances
 */
function render_archetype_instances(Page $archetype, array $instances): void
{
    ?>
    <h1><?= htmlspecialchars($archetype->label()) ?> instances</h1>
    <p>
        <a href="/admin/archetypes/<?= htmlspecialchars($archetype->slug()) ?>/new">
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
                        "/admin/archetypes/{$archetype->slug()}/{$instance->id}/edit",
                        "/admin/archetypes/{$archetype->slug()}/{$instance->id}/delete"
                    ); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}
