<?php

namespace VanillaCms\Uploads;

use VanillaCms\Fields\TextField;

class GenericUploadMeta extends UploadMeta
{
    public TextField $title;

    public function __construct()
    {
        $this->title = new TextField(['label' => 'Title']);
    }
}
