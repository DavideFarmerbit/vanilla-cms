<?php

namespace VanillaCms\Uploads;

use VanillaCms\Fields\TextField;
use VanillaCms\Storage\Storage;

class ImageUploadMeta extends UploadMeta
{
    public TextField $alt;
    public TextField $title;

    public function __construct()
    {
        $this->alt = new TextField(['label' => 'Alt text']);
        $this->title = new TextField(['label' => 'Title']);
    }

    /**
     * Builds the `srcset` attribute value for a set of widths, lazily generating any variant that doesn't
     * exist on disk yet. Returns null if no image generator is configured, or none of the requested widths
     * could be produced (e.g. this upload isn't a raster format the generator can read, like svg).
     * @param int[] $widths list of widths needed for the srcset in px.
     * @return string|null
     */
    public function srcset(array $widths): ?string
    {
        $parts = [];
        foreach (array_unique($widths) as $width) {
            $url = Storage::ensureImageVariant($this->path(), $width);
            if ($url !== null) {
                $parts[] = "{$url} {$width}w";
            }
        }
        return $parts !== [] ? implode(', ', $parts) : null;
    }
}
