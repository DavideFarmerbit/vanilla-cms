<?php

use VanillaCms\Auth\Csrf;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Core\Registry\TypeRegistry;
use VanillaCms\Core\Router\Router;
use VanillaCms\Storage\PageData;
use VanillaCms\Storage\Storage;

function render_page_editor(Page $instance, string $backUrl, string $saveAction, ?string $deleteAction, bool $isNew): void
{
    $pageUrl = htmlspecialchars($instance->url());

    ?>
    <h1 class="vcms-page-title"><?= $isNew ? 'Create' : 'Edit' ?> <?= htmlspecialchars($instance->label()) ?></h1>

    <?php if ($pageUrl): ?>
        <a class="vcms-link vcms-link--preview" href="<?= $pageUrl ?>"><?= $pageUrl ?></a>
    <?php else: ?>
        <span class="vcms-link vcms-link--preview">create the page to see the url</span>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($saveAction) ?>" class="vcms-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <div class="vcms-field vcms-field--text">
            <label class="vcms-field__label">
                Name
                <input class="vcms-field__input" type="text" name="name" value="<?= htmlspecialchars($instance->meta()->name()) ?>" required>
            </label>
        </div>
        <div class="vcms-field vcms-field--text">
            <label class="vcms-field__label">
                Slug
                <input class="vcms-field__input" type="text" name="slug" value="<?= htmlspecialchars($instance->meta()->slug()) ?>" required>
            </label>
        </div>
        <?php foreach ($instance->getFields() as $fieldName => $field) {
            $field->render("fields[{$fieldName}]", []);
        }

        ?>
        <div class="vcms-form__actions">
            <button type="submit" class="vcms-btn vcms-btn--primary">Save</button>
            <a class="vcms-btn vcms-btn--link" href="<?= htmlspecialchars($backUrl) ?>">Cancel</a>
        </div>
    </form>
    <?php if ($deleteAction): ?>
        <form method="post" action="<?= htmlspecialchars($deleteAction) ?>" class="vcms-delete-form" data-confirm="Delete this entry? This cannot be undone.">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <button type="submit" class="vcms-btn vcms-btn--danger">Delete</button>
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