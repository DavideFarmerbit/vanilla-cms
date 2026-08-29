<?php

namespace VanillaCms\Fields;

use VanillaCms\Uploads\ImageUploadMeta;

/** 
 * @extends FileField<ImageUploadMeta>
 */
class ImageField extends FileField
{
    protected function allowedType(): string
    {
        return 'image';
    }
}
