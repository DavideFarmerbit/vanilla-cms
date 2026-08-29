<?php

namespace VanillaCms\Storage;

interface StorageDriver
{
    public function allPageInstances(string $typeSlug): array;

    public function findPageInstance(string $typeSlug, string $id): ?PageData;

    public function findPageInstanceBySlug(string $typeSlug, string $instanceSlug): ?PageData;

    public function findFirstPageInstance(string $typeSlug): ?PageData;

    public function savePageInstance(string $typeSlug, ?string $id, PageData $data): string;

    public function deletePageInstance(string $typeSlug, string $id): void;

    public function newId(): string;
}