<?php

namespace VanillaCms\Admin\Tabs;

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminTab;
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

        render_uploads_library($filtered, $uploads, self::getUploadsUrl(), $typeFilter, $yearFilter, $monthFilter);
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
            $path = Storage::storeUploadedFile($files['tmp_name'][$i], $id, $extension);

            $data = UploadData::empty();
            $data->type = UploadTypeRegistry::typeForExtension($extension);
            $data->name = pathinfo($originalName, PATHINFO_FILENAME);
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
            $uploadData->name = trim($_POST['name'] ?? '');
            $uploadData->fields = $_POST['fields'] ?? [];

            Storage::saveUpload($uploadData->id, $uploadData);
            Router::redirect(self::getUploadEditUrl($uploadData->id, $batch));
        }

        $meta = UploadMeta::instantiate($uploadData);
        render_upload_editor(
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
}