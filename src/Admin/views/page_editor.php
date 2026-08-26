<?php

use VanillaCms\Core\Registry\Page;

function render_page_editor(Page $type, ?PageData $instance, string $backUrl, string $saveAction, ?string $deleteAction): void
{
    ?>
    <h1><?= $instance ? 'Edit' : 'Create' ?> <?= htmlspecialchars($type->label()) ?></h1>
    <form method="post" action="<?= htmlspecialchars($saveAction) ?>" class="admin-form">
        <label>
            Name
            <input type="text" name="name" value="<?= htmlspecialchars($instance->name ?? '') ?>" required>
        </label>
        <label>
            Slug
            <input type="text" name="slug" value="<?= htmlspecialchars($instance->slug ?? '') ?>" required>
        </label>
        <div class="admin-form-actions">
            <button type="submit">Save</button>
            <a href="<?= htmlspecialchars($backUrl) ?>">Cancel</a>
        </div>
    </form>
    <?php if ($deleteAction): ?>
        <form method="post" action="<?= htmlspecialchars($deleteAction) ?>" class="admin-delete-form" data-confirm="Delete this entry? This cannot be undone.">
            <button type="submit" class="admin-danger">Delete</button>
        </form>
    <?php endif; ?>
    <?php
}
