<?php

namespace VanillaCms\Uploads;

use VanillaCms\Fields\Field;
use VanillaCms\Fields\FieldReflection;
use VanillaCms\Storage\Storage;
use VanillaCms\Storage\UploadData;

/**
 * Parent class for all upload metadata types (image, pdf, ...).
 * Add Field members to a subclass to make them editable in the admin panel's upload editor.
 */
abstract class UploadMeta
{
    private string $id;
    private string $type;
    private string $name;

    private string $path;
    private string $originalName;
    private string $mimeType;
    private int $size;
    private int $uploadedAt;

    public static function instantiate(UploadData $data): self
    {
        $class = UploadTypeRegistry::classForType($data->type);

        /** @var self $instance */
        $instance = new $class();

        $instance->id = $data->id;
        $instance->type = $data->type;
        $instance->name = $data->name;

        $instance->path = $data->path;
        $instance->originalName = $data->originalName;
        $instance->mimeType = $data->mimeType;
        $instance->size = $data->size;
        $instance->uploadedAt = $data->uploadedAt;

        foreach ($instance->getFields() as $fieldName => $field) {
            $field->fromArray($data->fields[$fieldName] ?? []);
        }

        return $instance;
    }

    public function toUploadData(): UploadData
    {
        $data = UploadData::empty();

        $data->id = $this->id;
        $data->type = $this->type;
        $data->name = $this->name;

        $data->path = $this->path;
        $data->originalName = $this->originalName;
        $data->mimeType = $this->mimeType;
        $data->size = $this->size;
        $data->uploadedAt = $this->uploadedAt;

        $data->fields = array_map(fn (Field $field) => $field->toArray(), $this->getFields());

        return $data;
    }

    /*================================================================================================================*/
    // DataInterface

    public function id(): string
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function uploadedAt(): int
    {
        return $this->uploadedAt;
    }

    public function url(): string
    {
        return Storage::uploadUrl($this->path);
    }

    // ~DataInterface
    /*================================================================================================================*/

    /*================================================================================================================*/
    // Reflection

    /**
     * Get all the Fields of this class.
     * @return array<string, Field> Name => Field
     */
    public final function getFields(): array
    {
        return FieldReflection::getFields($this);
    }

    // ~Reflection
    /*================================================================================================================*/
}
