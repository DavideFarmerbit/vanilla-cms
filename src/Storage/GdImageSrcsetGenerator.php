<?php

namespace VanillaCms\Storage;

use GdImage;

/**
 * Default ImageSrcsetGenerator backed by the GD extension (bundled with PHP on virtually every host).
 * Supports jpeg, png, gif and webp; returns false for anything else (e.g. svg, which GD can't rasterize).
 */
final class GdImageSrcsetGenerator implements ImageSrcsetGenerator
{
    public function __construct(
        private readonly int $jpegQuality = 82,
        private readonly int $webpQuality = 82,
        private readonly int $pngCompression = 6,
    ) {
    }

    public function resize(string $sourcePath, string $destPath, int $width): bool
    {
        if (!extension_loaded('gd') || $width <= 0) {
            return false;
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return false;
        }
        [$sourceWidth, $sourceHeight, $type] = $info;

        // Never upscale: if the requested width is at least the original's, the "variant" is just the original.
        if ($width >= $sourceWidth) {
            return copy($sourcePath, $destPath);
        }

        $source = $this->read($sourcePath, $type);
        if ($source === false) {
            return false;
        }

        $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));
        $resized = imagecreatetruecolor($width, $height);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF || $type === IMAGETYPE_WEBP) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        imagedestroy($source);

        $saved = $this->write($resized, $destPath, $type);
        imagedestroy($resized);

        return $saved;
    }

    private function read(string $path, int $type): GdImage|false
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function write(GdImage $image, string $path, int $type): bool
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, $this->jpegQuality),
            IMAGETYPE_PNG => imagepng($image, $path, $this->pngCompression),
            IMAGETYPE_GIF => imagegif($image, $path),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($image, $path, $this->webpQuality) : false,
            default => false,
        };
    }
}
