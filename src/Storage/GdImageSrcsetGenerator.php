<?php

namespace VanillaCms\Storage;

use GdImage;

/**
 * Default ImageSrcsetGenerator backed by the GD extension (bundled with PHP on virtually every host).
 * Reads jpeg, png, gif or webp sources and always writes webp variants (returns false for anything else,
 * e.g. svg, which GD can't rasterize).
 */
final class GdImageSrcsetGenerator implements ImageSrcsetGenerator
{
    public function __construct(
        private readonly int $webpQuality = 82,
    ) {
    }

    public function outputExtension(): string
    {
        return 'webp';
    }

    public function resize(string $sourcePath, string $destPath, int $width): bool
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp') || $width <= 0) {
            return false;
        }

        $info = @getimagesize($sourcePath);
        if ($info === false) {
            return false;
        }
        [$sourceWidth, $sourceHeight, $type] = $info;

        $source = $this->read($sourcePath, $type);
        if ($source === false) {
            return false;
        }

        // Never upscale: cap the target width at the original's.
        $width = min($width, $sourceWidth);
        $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));

        if ($width === $sourceWidth && $height === $sourceHeight) {
            $resized = $source;
        } else {
            $resized = imagecreatetruecolor($width, $height);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $width, $height, $transparent);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
            imagedestroy($source);
        }

        $saved = imagewebp($resized, $destPath, $this->webpQuality);
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
}
