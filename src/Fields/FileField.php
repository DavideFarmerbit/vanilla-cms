<?php

namespace VanillaCms\Fields;

use VanillaCms\Storage\Storage;
use VanillaCms\Uploads\UploadMeta;

/**
 * Base class for fields that reference an upload managed through the Admin > Uploads library
 * (see VanillaCms\Uploads\UploadMeta). Only the upload's id is stored here; its metadata (name,
 * alt text, ...) lives on the upload record itself. Add a new file-related field by extending this
 * and implementing allowedType() with the UploadTypeRegistry type key it should be restricted to.
 */
abstract class FileField extends Field
{
    private ?string $uploadId = null;
    private ?UploadMeta $resolvedUpload = null;
    private bool $uploadResolved = false;

    /**
     * The UploadTypeRegistry type key (e.g. 'image', 'pdf') this field may reference.
     */
    abstract protected function allowedType(): string;

    public function toArray(): array
    {
        return ['uploadId' => $this->uploadId];
    }

    public function fromArray(array $data): void
    {
        $uploadId = $data['uploadId'] ?? '';
        $this->uploadId = $uploadId !== '' ? $uploadId : null;
        $this->resolvedUpload = null;
        $this->uploadResolved = false;
    }

    /**
     * Resolves the referenced upload, or null if none is set or it was since deleted.
     */
    public function upload(): ?UploadMeta
    {
        if (!$this->uploadResolved) {
            $data = $this->uploadId !== null ? Storage::findUpload($this->uploadId) : null;
            $this->resolvedUpload = $data !== null ? UploadMeta::instantiate($data) : null;
            $this->uploadResolved = true;
        }

        return $this->resolvedUpload;
    }

    public function url(): ?string
    {
        return $this->upload()?->url();
    }

    public function id(): ?string
    {
        return $this->uploadId;
    }

    public function render(string $name): void
    {
        $upload = $this->upload();
        ?>
        <div class="vcms-field vcms-file-field" data-vcms-file-field data-allowed-type="<?= htmlspecialchars($this->allowedType()) ?>">
            <div class="vcms-field__label"><?= htmlspecialchars($this->config['label'] ?? 'value') ?></div>
            <input type="hidden" name="<?= "{$name}[uploadId]" ?>" value="<?= htmlspecialchars($this->uploadId ?? '') ?>" data-vcms-file-field-input>
            <div class="vcms-file-field__preview" data-vcms-file-field-preview>
                <?php $this->renderPreview($upload); ?>
            </div>
            <div class="vcms-file-field__actions">
                <button type="button" class="vcms-btn vcms-btn--action" data-vcms-file-field-open>Choose</button>
                <button type="button" class="vcms-btn vcms-btn--action" data-vcms-file-field-clear>Clear</button>
            </div>
        </div>
        <?php
    }

    protected function renderPreview(?UploadMeta $upload): void
    {
        if ($upload === null) {
            ?>
            <span class="vcms-file-field__name" data-vcms-file-field-name>No file selected</span>
            <?php
            return;
        }

        if ($upload->type() === 'image') {
            ?>
            <img class="vcms-upload-grid__thumb" src="<?= htmlspecialchars($upload->url()) ?>" alt="" data-vcms-file-field-thumb>
            <?php
        } else {
            ?>
            <span class="vcms-upload-grid__ext" data-vcms-file-field-thumb><?= htmlspecialchars(strtoupper($upload->extension())) ?></span>
            <?php
        }
        ?>
        <span class="vcms-file-field__name" data-vcms-file-field-name><?= htmlspecialchars($upload->name() ?: $upload->originalName()) ?></span>
        <?php
    }
}
