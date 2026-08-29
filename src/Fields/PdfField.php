<?php

namespace VanillaCms\Fields;

use VanillaCms\Uploads\PdfUploadMeta;

/**
 * @extends FileField<PdfUploadMeta>
 */
class PdfField extends FileField
{
    protected function allowedType(): string
    {
        return 'pdf';
    }
}
