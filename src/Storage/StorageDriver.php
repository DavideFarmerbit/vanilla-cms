<?php

interface StorageDriver
{
    public function all(string $typeSlug): array;

    public function find(string $typeSlug, string $id): ?PageData;

    public function findBySlug(string $typeSlug, string $instanceSlug): ?PageData;

    public function findFirst(string $typeSlug): ?PageData;

    public function save(string $typeSlug, ?string $id, PageData $data): string;

    public function delete(string $typeSlug, string $id): void;
}