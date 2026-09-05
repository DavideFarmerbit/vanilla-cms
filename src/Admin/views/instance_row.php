<?php

use VanillaCms\Auth\Csrf;
use VanillaCms\Pages\Page;
use VanillaCms\Storage\PageData;

function render_instance_row_header()
{
    ?>
    <tr>
        <th class="vcms-table__cell">Name</th>
        <th class="vcms-table__cell">Type</th>
        <th class="vcms-table__cell">ID</th>
        <th class="vcms-table__cell">Visibility</th>
        <th class="vcms-table__cell"></th>
    </tr>
    <?php
}

function render_instance_row(Page $type, ?PageData $instance, string $editUrl, string $deleteUrl): void
{
    $name = ($instance?->name ?: $instance?->slug) ?: '-';
    $typeLabel = $type->label() ?: $type->slug();
    $id = $instance?->id ?: '-';
    $editLabel = $instance ? 'Edit' : 'Create';
    ?>
    <tr>
        <td class="vcms-table__cell"><?= htmlspecialchars($name) ?></td>
        <td class="vcms-table__cell"><?= htmlspecialchars($typeLabel) ?></td>
        <td class="vcms-table__cell"><?= htmlspecialchars($id) ?></td>
        <td class="vcms-table__cell"><?= htmlspecialchars($instance->visibility->label()) ?></td>
        <td class="vcms-table__cell vcms-table__cell--actions">
            <div class="vcms-table__actions">
                <a class="vcms-icon-btn vcms-icon-btn--confirm" href="<?= htmlspecialchars($editUrl) ?>" title="<?= $editLabel ?>" aria-label="<?= $editLabel ?>">
                    <?php vcms_icon($instance ? 'edit' : 'add') ?>
                </a>
                <?php if ($instance): ?>
                    <form method="post" action="<?= htmlspecialchars($deleteUrl) ?>" data-confirm="Delete this entry? This cannot be undone.">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <button type="submit" class="vcms-icon-btn vcms-icon-btn--danger" title="Delete" aria-label="Delete">
                            <?php vcms_icon('trash') ?>
                        </button>
                    </form>
                <?php else: ?>
                    <span class="vcms-icon-btn vcms-icon-btn--danger vcms-icon-btn--disabled" title="Delete" aria-label="Delete">
                        <?php vcms_icon('trash') ?>
                    </span>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
}
