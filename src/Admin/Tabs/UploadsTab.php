<?php

namespace VanillaCms\Admin\Tabs;

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminTab;
use VanillaCms\Auth\Csrf;
use VanillaCms\Core\Router\Router;
use VanillaCms\Storage\Storage;
use VanillaCms\Storage\UploadData;
use VanillaCms\Uploads\UploadMeta;
use VanillaCms\Uploads\UploadTypeRegistry;

class UploadsTab extends AdminTab
{
    public function __construct()
    {
        parent::__construct('uploads', 'Uploads');
    }

    public static function getUploadsUrl(): string {
        return "/admin/uploads";
    }

    /**
     * @param string[] $batch ids of the uploads to move prev/next between; omitted when editing outside a batch.
     */
    public static function getUploadEditUrl(string $id, array $batch = []): string {
        $url = "/admin/uploads/{$id}/edit";
        if (!empty($batch)) {
            $url .= '?' . http_build_query(['batch' => implode(',', $batch)]);
        }
        return $url;
    }

    public static function getUploadDeleteUrl(string $id): string {
        return "/admin/uploads/{$id}/delete";
    }
    
    public function handleApiRequest(array $segments): bool
    {
        if ($segments === ['api']) {
            $this->uploadsApiResponse();
            return true;
        }
        return false;
    }

    public function dispatch(array $segments): void
    {
        if (count($segments) === 0) {
            if (AdminController::isVerifiedPost()) {
                $this->handleUpload();
                return;
            }
            $this->renderUploadsLibrary();
            return;
        }

        $id = $segments[0];
        $subAction = $segments[1] ?? null;
        $uploadData = Storage::findUpload($id);

        if (!$uploadData) {
            Router::notFound();
            return;
        }

        if ($subAction === 'edit') {
            $this->handleUploadEditor($uploadData);
            return;
        }

        if ($subAction === 'delete' && AdminController::isVerifiedPost()) {
            Storage::deleteUpload($id);
            Router::redirect(self::getUploadsUrl());
        }

        Router::notFound();
    }

    protected function renderUploadsLibrary(): void
    {
        $uploads = array_map(fn (UploadData $data) => UploadMeta::instantiate($data), Storage::allUploads());

        $typeFilter = $_GET['type'] ?? '';
        $yearFilter = $_GET['year'] ?? '';
        $monthFilter = $_GET['month'] ?? '';

        $filtered = $this->filterUploads($uploads, $typeFilter, $yearFilter, $monthFilter);
        usort($filtered, fn (UploadMeta $a, UploadMeta $b) => $b->uploadedAt() <=> $a->uploadedAt());

        $this->renderUploadsLibraryMarkdown($filtered, $uploads, self::getUploadsUrl(), $typeFilter, $yearFilter, $monthFilter);
    }

    /**
     * JSON endpoint backing the file field picker modal: filtered, paginated upload metadata.
     */
    protected function uploadsApiResponse(): void
    {
        $uploads = array_map(fn (UploadData $data) => UploadMeta::instantiate($data), Storage::allUploads());

        $typeFilter = $_GET['type'] ?? '';
        $yearFilter = $_GET['year'] ?? '';
        $monthFilter = $_GET['month'] ?? '';
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $limit = 24;

        $filtered = $this->filterUploads($uploads, $typeFilter, $yearFilter, $monthFilter);
        usort($filtered, fn (UploadMeta $a, UploadMeta $b) => $b->uploadedAt() <=> $a->uploadedAt());

        $page = array_slice($filtered, $offset, $limit);

        $items = array_map(fn (UploadMeta $upload) => [
            'id' => $upload->id(),
            'name' => $upload->name() ?: $upload->originalName(),
            'thumb' => $upload->type() === 'image' ? $upload->url() : '',
            'ext' => strtoupper($upload->extension()),
            'uploadedAt' => $upload->uploadedAt(),
        ], $page);

        header('Content-Type: application/json');
        echo json_encode([
            'items' => $items,
            'hasMore' => $offset + $limit < count($filtered),
        ]);
    }

    protected function handleUpload(): void
    {
        $ids = [];
        $files = $_FILES['files'] ?? null;

        for ($i = 0; $files && $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['name'][$i] === '') {
                continue;
            }

            $originalName = $files['name'][$i];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!UploadTypeRegistry::isExtensionAllowed($extension)) {
                continue;
            }

            $mimeType = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $files['tmp_name'][$i]) ?: '';
                finfo_close($finfo);
            }

            $id = Storage::newId();
            $name = pathinfo($originalName, PATHINFO_FILENAME);
            $path = Storage::storeUploadedFile($files['tmp_name'][$i], $name, $extension);

            $data = UploadData::empty();
            $data->type = UploadTypeRegistry::typeForExtension($extension);
            $data->name = $name;
            $data->path = $path;
            $data->originalName = $originalName;
            $data->mimeType = $mimeType;
            $data->size = $files['size'][$i];
            $data->uploadedAt = time();

            Storage::saveUpload($id, $data);
            $ids[] = $id;
        }

        if (empty($ids)) {
            Router::redirect(self::getUploadsUrl());
        }

        Router::redirect(self::getUploadEditUrl($ids[0], $ids));
    }

    protected function handleUploadEditor(UploadData $uploadData): void
    {
        $batch = isset($_GET['batch']) ? array_values(array_filter(explode(',', $_GET['batch']))) : [];

        if (AdminController::isVerifiedPost()) {
            $name = trim($_POST['name'] ?? '');
            if ($name !== '') {
                $uploadData->path = Storage::renameUploadedFile($uploadData->path, $name);
                $uploadData->name = $name;
            }
            $uploadData->fields = $_POST['fields'] ?? [];

            Storage::saveUpload($uploadData->id, $uploadData);
            Router::redirect(self::getUploadEditUrl($uploadData->id, $batch));
        }

        $meta = UploadMeta::instantiate($uploadData);
        $this->renderUploadEditorMarkdown(
            $meta,
            self::getUploadsUrl(),
            self::getUploadEditUrl($meta->id(), $batch),
            self::getUploadDeleteUrl($meta->id()),
            $batch
        );
    }

    /** @param UploadMeta[] $uploads @return UploadMeta[] */
    protected function filterUploads(array $uploads, string $typeFilter, string $yearFilter, string $monthFilter): array
    {
        return array_values(array_filter($uploads, function (UploadMeta $upload) use ($typeFilter, $yearFilter, $monthFilter) {
            if ($typeFilter !== '' && $upload->type() !== $typeFilter) {
                return false;
            }
            if ($yearFilter !== '' && date('Y', $upload->uploadedAt()) !== $yearFilter) {
                return false;
            }
            if ($monthFilter !== '' && date('m', $upload->uploadedAt()) !== $monthFilter) {
                return false;
            }
            return true;
        }));
    }

    /**
     * @param UploadMeta[] $uploads uploads matching the current filter, to display.
     * @param UploadMeta[] $allUploads every upload, used to compute the year filter's options.
     */
    public function renderUploadsLibraryMarkdown(array $uploads, array $allUploads, string $uploadAction, string $typeFilter, string $yearFilter, string $monthFilter): void
    {
        $years = array_values(array_unique(array_map(fn (UploadMeta $upload) => date('Y', $upload->uploadedAt()), $allUploads)));
        rsort($years);

        $months = vcms_months();
        ?>
        <h1 class="vcms-page-title">Uploads</h1>

        <form method="get" class="vcms-upload-filters">
            <label class="vcms-field__label">
                Type
                <span class="vcms-field__select-wrap">
                <select class="vcms-field__input" name="type" onchange="this.form.submit()">
                    <option value="">All types</option>
                    <?php foreach (UploadTypeRegistry::types() as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $type === $typeFilter ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($type)) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
            </label>
            <label class="vcms-field__label">
                Year
                <span class="vcms-field__select-wrap">
                <select class="vcms-field__input" name="year" onchange="this.form.submit()">
                    <option value="">All years</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= htmlspecialchars($year) ?>" <?= $year === $yearFilter ? 'selected' : '' ?>><?= htmlspecialchars($year) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
            </label>
            <label class="vcms-field__label">
                Month
                <span class="vcms-field__select-wrap">
                <select class="vcms-field__input" name="month" onchange="this.form.submit()">
                    <option value="">All months</option>
                    <?php foreach ($months as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $value === $monthFilter ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
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
                    <a class="vcms-upload-grid__item" href="<?= htmlspecialchars(self::getUploadEditUrl($upload->id())) ?>">
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

    /** @param string[] $batch ids of the uploads to move prev/next between; empty outside of a batch. */
    function renderUploadEditorMarkdown(UploadMeta $upload, string $backUrl, string $saveAction, string $deleteAction, array $batch): void
    {
        $prevUrl = null;
        $nextUrl = null;
        $index = array_search($upload->id(), $batch, true);

        if ($index !== false) {
            if ($index > 0) {
                $prevUrl = self::getUploadEditUrl($batch[$index - 1], $batch);
            }
            if ($index < count($batch) - 1) {
                $nextUrl = self::getUploadEditUrl($batch[$index + 1], $batch);
            }
        }
        ?>
        <div class="vcms-page-header">
            <a class="vcms-icon-btn vcms-page-header__back" href="<?= htmlspecialchars($backUrl) ?>" title="Back" aria-label="Back">
                <?php vcms_icon('back') ?>
            </a>
            <h1 class="vcms-page-title">Edit <?= htmlspecialchars($upload->basename()) ?></h1>
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
                <label class="vcms-field__label">
                    Url:
                    <input class="vcms-field__input" type="text" readonly value="<?= htmlspecialchars($upload->url()) ?>" onclick="this.select()">
                </label>
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
}