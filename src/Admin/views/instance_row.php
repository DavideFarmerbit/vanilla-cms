<?php

use VanillaCms\Auth\Csrf;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Storage\PageData;

function render_instance_row_header()
{
    ?>
    <tr>
        <th class="vcms-table__cell">Name</th>
        <th class="vcms-table__cell">Type</th>
        <th class="vcms-table__cell">ID</th>
        <th class="vcms-table__cell"></th>
        <th class="vcms-table__cell"></th>
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
        <td class="vcms-table__cell"><?= htmlspecialchars($name) ?></td>
        <td class="vcms-table__cell"><?= htmlspecialchars($typeLabel) ?></td>
        <td class="vcms-table__cell"><?= htmlspecialchars($id) ?></td>
        <td class="vcms-table__cell vcms-table__cell--actions">
            <a class="vcms-btn vcms-btn--link" href="<?= htmlspecialchars($editUrl) ?>"><?= $instance ? 'Edit' : 'Create' ?></a>
        </td>
        <td class="vcms-table__cell vcms-table__cell--actions">
            <?php if ($instance): ?>
                <form method="post" action="<?= htmlspecialchars($deleteUrl) ?>" data-confirm="Delete this entry? This cannot be undone.">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button type="submit" class="vcms-btn vcms-btn--danger">Delete</button>
                </form>
            <?php else: ?>
                <span class="vcms-btn vcms-btn--link vcms-btn--disabled">Delete</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
