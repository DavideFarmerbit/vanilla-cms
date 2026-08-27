<?php

use VanillaCms\Auth\Csrf;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Storage\PageData;

function render_page_editor(Page $type, ?PageData $instance, string $backUrl, string $saveAction, ?string $deleteAction): void
{
    ?>
    <h1><?= $instance ? 'Edit' : 'Create' ?> <?= htmlspecialchars($type->label()) ?></h1>
    <?php $pageUrl = isset($instance) ? htmlspecialchars($type->url($instance)) : null; ?>
    <?php if ($deleteAction): ?>
        <a class="preview-url" href="<?= $pageUrl ?>"><?= $pageUrl ?></a>
    <?php else: ?>
        <span class="preview-url">create the page to see the url</span>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($saveAction) ?>" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <button type="submit" class="admin-danger">Delete</button>
        </form>
    <?php endif; ?>
    <?php
}
