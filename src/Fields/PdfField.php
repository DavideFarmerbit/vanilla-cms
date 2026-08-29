<?php

namespace VanillaCms\Fields;

class PdfField extends FileField
{
    protected function allowedType(): string
    {
        return 'pdf';
    }
}
