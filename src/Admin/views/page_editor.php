<?php

use VanillaCms\Auth\Csrf;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\Router;
use VanillaCms\Storage\PageData;
use VanillaCms\Storage\Storage;

function render_page_editor(Page $type, ?Page $instance, string $backUrl, string $saveAction, ?string $deleteAction): void
{
    $pageUrl = $instance ? htmlspecialchars($instance->url()) : null;

    ?>
    <h1><?= $instance ? 'Edit' : 'Create' ?> <?= htmlspecialchars($type->label()) ?></h1>
    
    <?php if ($pageUrl): ?>
        <a class="preview-url" href="<?= $pageUrl ?>"><?= $pageUrl ?></a>
    <?php else: ?>
        <span class="preview-url">create the page to see the url</span>
    <?php endif; ?>
        
    <form method="post" action="<?= htmlspecialchars($saveAction) ?>" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <label>
            Name
            <input type="text" name="name" value="<?= htmlspecialchars($instance?->meta()->name() ?? '') ?>" required>
        </label>
        <label>
            Slug
            <input type="text" name="slug" value="<?= htmlspecialchars($instance?->meta()->slug() ?? '') ?>" required>
        </label>
        <?php foreach ($type->getFields() as $fieldName => $field) {
            $field->render("fields[{$fieldName}]", []);
        }
            
        ?>
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

function collect_page_editor_response(Page $type): PageData 
{
    $data = PageData::empty();
    $data->setPage($type);
    
    $data->slug = TypeRegistry::sanitizeSlug($_POST['slug'] ?? '');
    $data->name = trim($_POST['name'] ?? '');
    
    $data->fields = $_POST['fields'] ?? [];
    
    return $data;
}