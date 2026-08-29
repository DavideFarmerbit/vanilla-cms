<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Auth\Csrf;
use VanillaCms\Uploads\UploadMeta;

/** @param string[] $batch ids of the uploads to move prev/next between; empty outside of a batch. */
function render_upload_editor(UploadMeta $upload, string $backUrl, string $saveAction, string $deleteAction, array $batch): void
{
    $prevUrl = null;
    $nextUrl = null;
    $index = array_search($upload->id(), $batch, true);

    if ($index !== false) {
        if ($index > 0) {
            $prevUrl = AdminController::getUploadEditUrl($batch[$index - 1], $batch);
        }
        if ($index < count($batch) - 1) {
            $nextUrl = AdminController::getUploadEditUrl($batch[$index + 1], $batch);
        }
    }
    ?>
    <div class="vcms-page-header">
        <a class="vcms-icon-btn vcms-page-header__back" href="<?= htmlspecialchars($backUrl) ?>" title="Back" aria-label="Back">
            <?php vcms_icon('back') ?>
        </a>
        <h1 class="vcms-page-title">Edit <?= htmlspecialchars($upload->originalName()) ?></h1>
        <?php if ($prevUrl || $nextUrl): ?>
            <div class="vcms-upload-editor__nav">
                <?php if ($prevUrl): ?>
                    <a class="vcms-btn vcms-btn--action" href="<?= htmlspecialchars($prevUrl) ?>">&larr; Prev</a>
                <?php else: ?>
                    <span class="vcms-btn vcms-btn--disabled">&larr; Prev</span>
                <?php endif; ?>
                <?php if ($nextUrl): ?>
                    <a class="vcms-btn vcms-btn--action" href="<?= htmlspecialchars($nextUrl) ?>">Next &rarr;</a>
                <?php else: ?>
                    <span class="vcms-btn vcms-btn--disabled">Next &rarr;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="vcms-upload-editor__preview">
        <?php if ($upload->type() === 'image'): ?>
            <img src="<?= htmlspecialchars($upload->url()) ?>" alt="<?= htmlspecialchars($upload->originalName()) ?>">
        <?php else: ?>
            <span class="vcms-upload-grid__ext"><?= htmlspecialchars(strtoupper($upload->extension())) ?></span>
        <?php endif; ?>
    </div>

    <ul class="vcms-upload-editor__info">
        <li>Original filename: <?= htmlspecialchars($upload->originalName()) ?></li>
        <li>Size: <?= htmlspecialchars(number_format($upload->size() / 1024, 1)) ?> KB</li>
        <li>Uploaded: <?= htmlspecialchars(date('Y-m-d H:i', $upload->uploadedAt())) ?></li>
        <li>
            Url:
            <input class="vcms-field__input" type="text" readonly value="<?= htmlspecialchars($upload->url()) ?>" onclick="this.select()">
        </li>
    </ul>

    <form method="post" action="<?= htmlspecialchars($saveAction) ?>" class="vcms-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <div class="vcms-field vcms-field--text">
            <label class="vcms-field__label">
                Name
                <input class="vcms-field__input" type="text" name="name" value="<?= htmlspecialchars($upload->name()) ?>" required>
            </label>
        </div>
        <?php foreach ($upload->getFields() as $fieldName => $field) {
            $field->render("fields[{$fieldName}]");
        } ?>
        <div class="vcms-form__actions">
            <button type="submit" class="vcms-btn vcms-btn--primary">Save</button>
        </div>
    </form>
    <form method="post" action="<?= htmlspecialchars($deleteAction) ?>" class="vcms-delete-form" data-confirm="Delete this upload? This cannot be undone.">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <button type="submit" class="vcms-btn vcms-btn--danger">Delete</button>
    </form>
    <?php
}
