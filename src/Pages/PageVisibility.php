<?php

namespace VanillaCms\Pages;

enum PageVisibility: string
{
    case HIDDEN = 'hidden';
    case RESTRICTED = 'restricted';
    case PUBLIC = 'public';
}
