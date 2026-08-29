<?php

namespace VanillaCms\Uploads;

use VanillaCms\Fields\TextField;

class PdfUploadMeta extends UploadMeta
{
    public TextField $title;
    public TextField $description;

    public function __construct()
    {
        $this->title = new TextField(['label' => 'Title']);
        $this->description = new TextField(['label' => 'Description']);
    }
}
