<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Auth\Csrf;
use VanillaCms\Uploads\UploadMeta;
use VanillaCms\Uploads\UploadTypeRegistry;

/**
 * @param UploadMeta[] $uploads uploads matching the current filter, to display.
 * @param UploadMeta[] $allUploads every upload, used to compute the year filter's options.
 */
function render_uploads_library(array $uploads, array $allUploads, string $uploadAction, string $typeFilter, string $yearFilter, string $monthFilter): void
{
    $years = array_values(array_unique(array_map(fn (UploadMeta $upload) => date('Y', $upload->uploadedAt()), $allUploads)));
    rsort($years);

    $months = vcms_months();
    ?>
    <h1 class="vcms-page-title">Uploads</h1>

    <form method="get" class="vcms-upload-filters">
        <label class="vcms-field__label">
            Type
            <select class="vcms-field__input" name="type" onchange="this.form.submit()">
                <option value="">All types</option>
                <?php foreach (UploadTypeRegistry::types() as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= $type === $typeFilter ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($type)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="vcms-field__label">
            Year
            <select class="vcms-field__input" name="year" onchange="this.form.submit()">
                <option value="">All years</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= htmlspecialchars($year) ?>" <?= $year === $yearFilter ? 'selected' : '' ?>><?= htmlspecialchars($year) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="vcms-field__label">
            Month
            <select class="vcms-field__input" name="month" onchange="this.form.submit()">
                <option value="">All months</option>
                <?php foreach ($months as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $value === $monthFilter ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <noscript><button type="submit" class="vcms-btn">Filter</button></noscript>
    </form>

    <form method="post" action="<?= htmlspecialchars($uploadAction) ?>" enctype="multipart/form-data" class="vcms-dropzone" data-vcms-dropzone>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
        <p class="vcms-dropzone__hint">Drag and drop files here, or</p>
        <input class="vcms-dropzone__input" type="file" name="files[]" multiple data-vcms-dropzone-input>
        <button type="submit" class="vcms-btn vcms-btn--primary">Upload</button>
    </form>

    <?php if (empty($uploads)): ?>
        <p class="vcms-empty-state">No uploads yet.</p>
    <?php else: ?>
        <div class="vcms-upload-grid">
            <?php foreach ($uploads as $upload): ?>
                <a class="vcms-upload-grid__item" href="<?= htmlspecialchars(AdminController::getUploadEditUrl($upload->id())) ?>">
                    <?php if ($upload->type() === 'image'): ?>
                        <img class="vcms-upload-grid__thumb" src="<?= htmlspecialchars($upload->url()) ?>" alt="">
                    <?php else: ?>
                        <span class="vcms-upload-grid__ext"><?= htmlspecialchars(strtoupper($upload->extension())) ?></span>
                    <?php endif; ?>
                    <span class="vcms-upload-grid__name"><?= htmlspecialchars($upload->name() ?: $upload->originalName()) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
}
