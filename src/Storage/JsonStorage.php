<?php

namespace VanillaCms\Storage;

class JsonStorage implements StorageDriver
{
    private string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
    }

    public function all(string $typeSlug): array
    {
        return $this->readCollection($typeSlug);
    }

    public function find(string $typeSlug, string $id): ?PageData
    {
        foreach ($this->readCollection($typeSlug) as $record) {
            if ($record->id === $id) {
                return $record;
            }
        }

        return null;
    }

    public function findBySlug(string $typeSlug, string $instanceSlug): ?PageData
    {
        foreach ($this->readCollection($typeSlug) as $record) {
            if ($record->slug === $instanceSlug) {
                return $record;
            }
        }

        return null;
    }

    public function findFirst(string $typeSlug): ?PageData
    {
        return $this->readCollection($typeSlug)[0] ?? null;
    }

    public function save(string $typeSlug, ?string $id, PageData $data): string
    {
        $records = $this->readCollection($typeSlug);
        $id ??= bin2hex(random_bytes(8));
        $data->id = $id;

        $replaced = false;

        foreach ($records as $i => $existing) {
            if ($existing->id === $id) {
                $records[$i] = $data;
                $replaced = true;
                break;
            }
        }

        if (!$replaced) {
            $records[] = $data;
        }

        $this->writeCollection($typeSlug, $records);

        return $id;
    }

    public function delete(string $typeSlug, string $id): void
    {
        $records = array_values(array_filter(
            $this->readCollection($typeSlug),
            fn (PageData $record) => $record->id !== $id
        ));

        $this->writeCollection($typeSlug, $records);
    }

    private function collectionPath(string $typeSlug): string
    {
        return $this->root . '/' . $typeSlug . '.json';
    }

    /** @return PageData[] */
    private function readCollection(string $typeSlug): array
    {
        $file = $this->collectionPath($typeSlug);

        if (!file_exists($file)) {
            return [];
        }

        $handle = fopen($file, 'r');
        flock($handle, LOCK_SH);
        $json = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $rows = json_decode($json, true) ?? [];

        return array_map([self::class, 'hydrate'], $rows);
    }

    /** @param PageData[] $records */
    private function writeCollection(string $typeSlug, array $records): void
    {
        if (!is_dir($this->root)) {
            mkdir($this->root, 0775, true);
        }

        $handle = fopen($this->collectionPath($typeSlug), 'c');
        flock($handle, LOCK_EX);
        ftruncate($handle, 0);
        fwrite($handle, json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private static function hydrate(array $row): PageData
    {
        $record = new PageData();
        $record->id = $row['id'];
        $record->type_slug = $row['type_slug'];
        $record->type_label = $row['type_label'];
        $record->is_archetype = $row['is_archetype'];
        $record->slug = $row['slug'];
        $record->name = $row['name'];
        $record->fields = $row['fields'] ?? [];

        return $record;
    }
}
