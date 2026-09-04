<?php

namespace VanillaCms\Storage;

use Exception;

final class Storage
{
    private static ?StorageDriver $driver = null;
    private static ?ImageSrcsetGenerator $imageSrcsetGenerator = null;

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
     * Set the generator used to produce resized image variants for srcset(). 
     * Optional: if never called, ensureImageVariant() (and ImageUploadMeta::srcset()) just returns null for every width.
     * @param ImageSrcsetGenerator $generator
     * @return void
     */
    public static function setImageSrcsetGenerator(ImageSrcsetGenerator $generator): void
    {
        self::$imageSrcsetGenerator = $generator;
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
            self::deleteGeneratedVariants($upload->path);
        }
        self::driver()->deleteUpload($id);
    }

    /**
     * Moves an uploaded file into the uploads root, under a year/month subfolder based on the current server time.
     * The stored filename is derived from $name (sanitized into a filesystem/url-safe slug), disambiguated with
     * a numeric suffix if a file with the same name already exists in that subfolder.
     * @param string $tmpPath path of the uploaded file (e.g. $_FILES[...]['tmp_name']).
     * @param string $name desired file name (e.g. the upload's display name), used to derive the stored filename.
     * @param string $extension file extension, without the leading dot.
     * @return string path of the stored file, relative to the uploads root.
     * @throws Exception if the uploads root is not set, or the file could not be moved.
     */
    public static function storeUploadedFile(string $tmpPath, string $name, string $extension): string
    {
        self::assertUploadsRootSet();

        $subDir = date('Y') . '/' . date('m');
        $dir = self::$uploadsRoot . '/' . $subDir;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $relativePath = $subDir . '/' . self::uniqueFileName($dir, $name, $extension);

        if (!move_uploaded_file($tmpPath, self::$uploadsRoot . '/' . $relativePath)) {
            throw new Exception('VanillaCms >> Failed to store uploaded file');
        }

        return $relativePath;
    }

    /**
     * Renames an already-stored uploaded file to match a new name, keeping it in its current year/month
     * subfolder. Safe to call even when no rename is actually needed (e.g. the name didn't change).
     * @param string $oldRelativePath current path of the file, relative to the uploads root.
     * @param string $newName desired new file name (e.g. the upload's updated display name).
     * @return string new path of the file, relative to the uploads root.
     * @throws Exception if the uploads root is not set, the file doesn't exist, or it could not be renamed.
     */
    public static function renameUploadedFile(string $oldRelativePath, string $newName): string
    {
        self::assertUploadsRootSet();

        $oldFullPath = self::$uploadsRoot . '/' . $oldRelativePath;
        if (!is_file($oldFullPath)) {
            throw new Exception('VanillaCms >> Cannot rename uploaded file: source file not found');
        }

        $subDir = dirname($oldRelativePath);
        $dir = self::$uploadsRoot . '/' . $subDir;
        $extension = pathinfo($oldRelativePath, PATHINFO_EXTENSION);

        $newRelativePath = $subDir . '/' . self::uniqueFileName($dir, $newName, $extension, $oldFullPath);

        if ($newRelativePath === $oldRelativePath) {
            return $oldRelativePath;
        }

        if (!rename($oldFullPath, self::$uploadsRoot . '/' . $newRelativePath)) {
            throw new Exception('VanillaCms >> Failed to rename uploaded file');
        }

        return $newRelativePath;
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
     * Returns the url of a resized copy of an uploaded image at the given width, generating and caching it
     * to disk the first time it's requested (subsequent calls just find it already on disk). The variant is
     * stored under a "generated/" subtree mirroring the original's year/month subfolder.
     * @param string $relativePath path of the original image, relative to the uploads root (UploadData::path).
     * @param int $width target width in pixels.
     * @return string|null null if no generator is configured, the source file is missing, or generation failed
     * (e.g. an unsupported format like svg).
     * @throws Exception if the uploads root is not set.
     */
    public static function ensureImageVariant(string $relativePath, int $width): ?string
    {
        if (self::$imageSrcsetGenerator === null) {
            return null;
        }

        self::assertUploadsRootSet();

        $generatedRelativePath = self::generatedVariantPath($relativePath, $width);
        $generatedFullPath = self::$uploadsRoot . '/' . $generatedRelativePath;

        if (is_file($generatedFullPath)) {
            return self::uploadUrl($generatedRelativePath);
        }

        $sourceFullPath = self::$uploadsRoot . '/' . $relativePath;
        if (!is_file($sourceFullPath)) {
            return null;
        }

        $generatedDir = dirname($generatedFullPath);
        if (!is_dir($generatedDir)) {
            mkdir($generatedDir, 0775, true);
        }

        // Generate into a temp file and rename into place, so a concurrent request for the same variant
        // never finds a partially-written file.
        $tmpPath = $generatedFullPath . '.' . uniqid('tmp', true);
        $generated = self::$imageSrcsetGenerator->resize($sourceFullPath, $tmpPath, $width) && rename($tmpPath, $generatedFullPath);
        if (!$generated) {
            @unlink($tmpPath);
            return null;
        }

        return self::uploadUrl($generatedRelativePath);
    }

    /**
     * Deletes any previously-generated srcset variants of an uploaded image, e.g. before it's renamed or
     * removed. Safe to call for uploads that never had variants generated (no-op).
     * @param string $relativePath path of the original image, relative to the uploads root.
     * @return void
     * @throws Exception if the uploads root is not set.
     */
    public static function deleteGeneratedVariants(string $relativePath): void
    {
        self::assertUploadsRootSet();

        $dir = self::$uploadsRoot . '/generated/' . dirname($relativePath);
        $base = pathinfo($relativePath, PATHINFO_FILENAME);
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        foreach (glob("{$dir}/{$base}-*w.{$extension}") ?: [] as $file) {
            unlink($file);
        }
    }

    /**
     * Builds the relative path (under the uploads root) a given width's variant of an image should live at.
     */
    private static function generatedVariantPath(string $relativePath, int $width): string
    {
        $subDir = dirname($relativePath);
        $base = pathinfo($relativePath, PATHINFO_FILENAME);
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        return "generated/{$subDir}/{$base}-{$width}w.{$extension}";
    }

    /**
     * Builds a filesystem/url-safe file name for $dir from $name and $extension, unique within that directory
     * (disambiguated with a numeric suffix, e.g. "-2", if needed). Falls back to a generated id if $name
     * sanitizes down to an empty string.
     * @param string $dir absolute directory the file will live in.
     * @param string $name desired file name, before sanitization.
     * @param string $extension file extension, without the leading dot.
     * @param string|null $ignoreFullPath an existing full path to exclude from the collision check (e.g. the
     * file currently being renamed, so renaming to its own resulting name is a no-op rather than a "-2").
     * @return string
     */
    private static function uniqueFileName(string $dir, string $name, string $extension, ?string $ignoreFullPath = null): string
    {
        $base = self::slugify($name);
        if ($base === '') {
            $base = self::driver()->newId();
        }

        $suffix = '';
        $attempt = 1;
        while (true) {
            $candidate = $base . $suffix . ($extension !== '' ? '.' . $extension : '');
            $candidateFullPath = $dir . '/' . $candidate;
            if ($candidateFullPath === $ignoreFullPath || !is_file($candidateFullPath)) {
                return $candidate;
            }
            $attempt++;
            $suffix = '-' . $attempt;
        }
    }

    /**
     * Sanitizes a string into a filesystem/url-safe slug: lowercase, transliterated to ASCII where possible
     * (if the intl extension is available), with anything else collapsed into dashes.
     */
    private static function slugify(string $name): string
    {
        $slug = strtolower($name);

        if (function_exists('transliterator_transliterate')) {
            $slug = transliterator_transliterate('Any-Latin; Latin-ASCII', $slug);
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
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