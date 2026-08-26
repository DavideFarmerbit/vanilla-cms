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
        <ul class="admin-list">
            <?php foreach ($instances as $instance): ?>
                <li>
                    <span><?= htmlspecialchars($instance->name) ?> (<?= htmlspecialchars($instance->slug) ?>)</span>
                    <a href="/admin/archetypes/<?= htmlspecialchars($archetype->slug()) ?>/<?= htmlspecialchars($instance->id) ?>/edit">
                        Edit
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php
}
