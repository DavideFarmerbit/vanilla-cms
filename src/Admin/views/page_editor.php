<?php

use VanillaCms\Auth\Csrf;
use VanillaCms\Core\PageRenderer;
use VanillaCms\Pages\Page;
use VanillaCms\Pages\PageTypeRegistry;
use VanillaCms\Pages\PageVisibility;
use VanillaCms\Storage\PageData;

function render_page_editor(Page $instance, string $backUrl, string $saveAction, ?string $deleteAction, bool $isNew): void
{
    $pageUrl = htmlspecialchars($instance->url());
    $previewUrl = htmlspecialchars($instance->url() . '?' . PageRenderer::PREVIEW_PARAM . '=1');

    ?>
    <div class="vcms-page-header">
        <a class="vcms-icon-btn vcms-page-header__back" href="<?= htmlspecialchars($backUrl) ?>" title="Back" aria-label="Back">
            <?php vcms_icon('back') ?>
        </a>
        <h1 class="vcms-page-title"><?= $isNew ? 'Create' : 'Edit' ?> <?= htmlspecialchars($instance->label()) ?></h1>
    </div>

    <?php if ($pageUrl): ?>
        <a class="vcms-link vcms-link--preview" href="<?= $previewUrl ?>" target="_blank"><?= $pageUrl ?></a>
    <?php else: ?>
        <span class="vcms-link vcms-link--preview">create the page to see the url</span>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($saveAction) ?>" class="vcms-form vcms-form--page-editor">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <div class="vcms-field vcms-field--select">
            <label class="vcms-field__label">
                Visibility
                <span class="vcms-field__select-wrap vcms-field__select-wrap--block">
                    <select class="vcms-field__input" name="visibility">
                        <?php foreach (PageVisibility::cases() as $visibilityOption): ?> ?>
                            <option value="<?= htmlspecialchars($visibilityOption->value) ?>" <?= $visibilityOption === $instance->visibility() ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($visibilityOption->value)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
        </div>
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
            <a class="vcms-btn vcms-btn--action" href="<?= htmlspecialchars($saveAction) ?>" data-confirm="Discard all unsaved changes?">Restore</a>
        </div>
    </form>
    <?php if ($deleteAction): ?>
        <form method="post" action="<?= htmlspecialchars($deleteAction) ?>" class="vcms-delete-form" data-confirm="Delete this entry? This cannot be undone.">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
            <button type="submit" class="vcms-btn vcms-btn--danger">Delete</button>
        </form>
    <?php endif; ?>
    <?php
    render_admin_file_picker_templates();
}

function collect_page_editor_response(Page $type): PageData 
{
    $data = PageData::empty();
    $data->setPage($type);
    
    $data->slug = PageTypeRegistry::sanitizeSlug($_POST['slug'] ?? '');
    $data->name = trim($_POST['name'] ?? '');
    $data->visibility =  PageVisibility::tryFrom($_POST['visibility'] ?? '') ?? PageVisibility::HIDDEN;
    
    $data->fields = $_POST['fields'] ?? [];
    
    return $data;
}