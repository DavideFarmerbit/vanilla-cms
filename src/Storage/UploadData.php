<?php

namespace VanillaCms\Storage;

class UploadData
{
    public string $id;
    public string $type;
    public string $name;

    // Physical file data
    public string $path;
    public string $originalName;
    public string $mimeType;
    public int $size;
    public int $uploadedAt;

    /** array view of the fields of the upload (Field::toArray) */
    public array $fields = [];

    public static function empty(): self
    {
        $data = new self();

        $data->id = '';
        $data->type = '';
        $data->name = '';

        $data->path = '';
        $data->originalName = '';
        $data->mimeType = '';
        $data->size = 0;
        $data->uploadedAt = 0;

        $data->fields = [];

        return $data;
    }
}
