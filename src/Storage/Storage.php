<?php

namespace VanillaCms\Storage;

use Exception;

final class Storage
{
    private static ?StorageDriver $driver = null;

    private static string $uploadsRoot = '';
    private static string $uploadsUrlBase = '';

    /**
     * Set the storage driver. Must be called before any other method.
     * @param StorageDriver $driver
     * @return void
     */
    public static function set(StorageDriver $driver): void
    {
        self::$driver = $driver;
    }

    /**
     * Set where uploaded files are physically stored, and the public url they're served from.
     * Must be called before any of the upload file methods. Independent of the storage driver:
     * uploaded files always live on disk regardless of which driver holds their metadata.
     * @param string $root filesystem directory to store uploaded files in (e.g. a public/uploads folder).
     * @param string $urlBase public url the $root directory is served from.
     * @return void
     */
    public static function setUploadsRoot(string $root, string $urlBase): void
    {
        self::$uploadsRoot = rtrim($root, '/');
        self::$uploadsUrlBase = rtrim($urlBase, '/');
    }


    /**
     * Get all instances of a page.
     * @param string $typeSlug slug of the registered page.
     * @return PageData[]
     * @throws Exception if storage driver is not set.
     */
    public static function allPageInstances(string $typeSlug): array
    {
        return self::driver()->allPageInstances($typeSlug);
    }

    /**
     * Get a specific instance of a page.
     * @param string $typeSlug slug of the registered page.
     * @param string $id unique identifier of the instance.
     * @return PageData|null
     * @throws Exception if storage driver is not set.
     */
    public static function findPageInstance(string $typeSlug, string $id): ?PageData
    {
        return self::driver()->findPageInstance($typeSlug, $id);
    }


    /**
     * Get a specific instance of a page.
     * @param string $typeSlug slug of the registered page.
     * @param string $instanceSlug slug of the instance.
     * @return PageData|null
     * @throws Exception if storage driver is not set.
     */
    public static function findPageInstanceBySlug(string $typeSlug, string $instanceSlug): ?PageData
    {
        return self::driver()->findPageInstanceBySlug($typeSlug, $instanceSlug);
    }

    /**
     * Get the first instance of a page.
     * @param string $typeSlug slug of the registered page.
     * @return PageData|null
     * @throws Exception if storage driver is not set.
     */
    public static function findFirstPageInstance(string $typeSlug): ?PageData
    {
        return self::driver()->findFirstPageInstance($typeSlug);
    }

    /**
     * Save a new instance of a page or update an existing one (the id from the data object is ignored).
     * @param string $typeSlug slug of the registered page.
     * @param string|null $id unique identifier of the instance.
     * @param PageData $data
     * @return string unique identifier of the instance.
     * @throws Exception if storage driver is not set or if the given type slug does not match the one in the data object.
     */
    public static function savePageInstance(string $typeSlug, ?string $id, PageData $data): string
    {
        if ($typeSlug !== $data->type_slug) {
            throw new Exception('JsonStorage::save >> given type slug does not match the one in the data object.');
        }
        return self::driver()->savePageInstance($typeSlug, $id, $data);
    }

    /**
     * Deletes a specific instance of a page.
     * @param string $typeSlug slug of the registered page.
     * @param string $id unique identifier of the instance.
     * @return void
     * @throws Exception if storage driver is not set.
     */
    public static function deletePageInstance(string $typeSlug, string $id): void
    {
        self::driver()->deletePageInstance($typeSlug, $id);
    }


    /**
     * Generate a new unique identifier for a page instance.
     * @return string
     * @throws Exception if storage driver is not set.
     */
    public static function newId(): string
    {
        return self::driver()->newId();
    }


    /**
     * Get all uploads.
     * @return UploadData[]
     * @throws Exception if storage driver is not set.
     */
    public static function allUploads(): array
    {
        return self::driver()->allUploads();
    }

    /**
     * Get a specific upload.
     * @param string $id unique identifier of the upload.
     * @return UploadData|null
     * @throws Exception if storage driver is not set.
     */
    public static function findUpload(string $id): ?UploadData
    {
        return self::driver()->findUpload($id);
    }

    /**
     * Save a new upload or update an existing one's metadata (the id from the data object is ignored).
     * @param string|null $id unique identifier of the upload.
     * @param UploadData $data
     * @return string unique identifier of the upload.
     * @throws Exception if storage driver is not set.
     */
    public static function saveUpload(?string $id, UploadData $data): string
    {
        return self::driver()->saveUpload($id, $data);
    }

    /**
     * Deletes an upload's metadata and its physical file.
     * @param string $id unique identifier of the upload.
     * @return void
     * @throws Exception if storage driver is not set.
     */
    public static function deleteUpload(string $id): void
    {
        $upload = self::driver()->findUpload($id);
        if ($upload !== null) {
            self::deleteUploadedFile($upload->path);
        }
        self::driver()->deleteUpload($id);
    }

    /**
     * Moves an uploaded file into the uploads root, under a year/month subfolder based on the current server time.
     * @param string $tmpPath path of the uploaded file (e.g. $_FILES[...]['tmp_name']).
     * @param string $id unique identifier of the upload, used as the stored filename to avoid trusting client input.
     * @param string $extension file extension, without the leading dot.
     * @return string path of the stored file, relative to the uploads root.
     * @throws Exception if the uploads root is not set, or the file could not be moved.
     */
    public static function storeUploadedFile(string $tmpPath, string $id, string $extension): string
    {
        self::assertUploadsRootSet();

        $subDir = date('Y') . '/' . date('m');
        $dir = self::$uploadsRoot . '/' . $subDir;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $relativePath = $subDir . '/' . $id . '.' . $extension;

        if (!move_uploaded_file($tmpPath, self::$uploadsRoot . '/' . $relativePath)) {
            throw new Exception('VanillaCms >> Failed to store uploaded file');
        }

        return $relativePath;
    }

    /**
     * Deletes a physical uploaded file.
     * @param string $relativePath path of the file, relative to the uploads root.
     * @return void
     * @throws Exception if the uploads root is not set.
     */
    public static function deleteUploadedFile(string $relativePath): void
    {
        self::assertUploadsRootSet();

        $fullPath = self::$uploadsRoot . '/' . $relativePath;
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * Builds the public url for an uploaded file.
     * @param string $relativePath path of the file, relative to the uploads root.
     * @return string
     * @throws Exception if the uploads root is not set.
     */
    public static function uploadUrl(string $relativePath): string
    {
        self::assertUploadsRootSet();

        return self::$uploadsUrlBase . '/' . $relativePath;
    }


    /**
     * Get the storage driver throwing an exception if not set.
     * @return StorageDriver
     * @throws Exception if storage driver is not set.
     */
    private static function driver(): StorageDriver
    {
        if (self::$driver === null) {
            throw new Exception('VanillaCms >> Storage driver not set');
        }
        return self::$driver;
    }

    /**
     * @throws Exception if the uploads root is not set.
     */
    private static function assertUploadsRootSet(): void
    {
        if (self::$uploadsRoot === '') {
            throw new Exception('VanillaCms >> Uploads root not set');
        }
    }
}