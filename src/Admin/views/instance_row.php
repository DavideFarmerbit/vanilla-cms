<?php

use VanillaCms\Core\Registry\Page;

function render_instance_row_header()
{
    ?>
    <tr>
        <th>Name</th>
        <th>Type</th>
        <th>ID</th>
        <th></th>
    </tr>
    <?php
}

function render_instance_row(Page $type, ?PageData $instance, string $editUrl, string $deleteUrl): void
{
    $name = ($instance?->name ?: $instance?->slug) ?: '-';
    $typeLabel = $type->label() ?: $type->slug();
    $id = $instance?->id ?: '-';
    ?>
    <tr>
        <td><?= htmlspecialchars($name) ?></td>
        <td><?= htmlspecialchars($typeLabel) ?></td>
        <td><?= htmlspecialchars($id) ?></td>
        <td class="admin-row-actions">
            <a href="<?= htmlspecialchars($editUrl) ?>"><?= $instance ? 'Edit' : 'Create' ?></a>
            <?php if ($instance): ?>
                <form method="post" action="<?= htmlspecialchars($deleteUrl) ?>" data-confirm="Delete this entry? This cannot be undone.">
                    <button type="submit" class="admin-danger">Delete</button>
                </form>
            <?php else: ?>
                <span class="admin-disabled">Delete</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
