<?php

namespace VanillaCms\Pages;

enum PageVisibility: string
{
    case HIDDEN = 'hidden';
    case RESTRICTED = 'restricted';
    case PUBLIC = 'public';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', strtolower($this->name)));
    }
}
