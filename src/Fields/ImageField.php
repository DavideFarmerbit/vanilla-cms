<?php

namespace VanillaCms\Fields;

class ImageField extends FileField
{
    protected function allowedType(): string
    {
        return 'image';
    }
}
