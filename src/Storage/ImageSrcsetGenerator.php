<?php

namespace VanillaCms\Storage;

/**
 * Optional capability, registered via Storage::setImageSrcsetGenerator(), that produces resized copies of
 * uploaded images for use in a srcset attribute. If none is registered, Storage::ensureImageVariant() (and
 * therefore ImageUploadMeta::srcset()) simply returns null.
 */
interface ImageSrcsetGenerator
{
    /**
     * Creates a resized copy of the image at $sourcePath and writes it to $destPath.
     * @param string $sourcePath absolute path of the original image.
     * @param string $destPath absolute path the resized copy should be written to; its parent directory
     * already exists.
     * @param int $width target width in pixels; height should scale proportionally.
     * @return bool true on success, false if the image could not be read or resized (e.g. corrupt or
     * unsupported format).
     */
    public function resize(string $sourcePath, string $destPath, int $width): bool;
}
