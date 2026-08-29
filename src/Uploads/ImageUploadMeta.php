<?php

namespace VanillaCms\Uploads;

use VanillaCms\Fields\TextField;

class ImageUploadMeta extends UploadMeta
{
    public TextField $alt;
    public TextField $title;

    public function __construct()
    {
        $this->alt = new TextField(['label' => 'Alt text']);
        $this->title = new TextField(['label' => 'Title']);
    }
}
