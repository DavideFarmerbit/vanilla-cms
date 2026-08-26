<?php

final class Storage
{
    private static ?StorageDriver $driver = null;

    /**
     * Set the storage driver. Must be called before any other method.
     * @param StorageDriver $driver
     * @return void
     */
    public static function set(StorageDriver $driver): void
    {
        self::$driver = $driver;
    }


    /**
     * Get all instances of a page.
     * @param string $typeSlug slug of the registered page.
     * @return PageData[]
     * @throws Exception if storage driver is not set.
     */
    public static function all(string $typeSlug): array
    {
        return self::driver()->all($typeSlug);
    }

    /**
     * Get a specific instance of a page.
     * @param string $typeSlug slug of the registered page.
     * @param string $id unique identifier of the instance.
     * @return PageData|null
     * @throws Exception if storage driver is not set.
     */
    public static function find(string $typeSlug, string $id): ?PageData
    {
        return self::driver()->find($typeSlug, $id);
    }


    /**
     * Get a specific instance of a page.
     * @param string $typeSlug slug of the registered page.
     * @param string $instanceSlug slug of the instance.
     * @return PageData|null
     * @throws Exception if storage driver is not set.
     */
    public static function findBySlug(string $typeSlug, string $instanceSlug): ?PageData
    {
        return self::driver()->findBySlug($typeSlug, $instanceSlug);
    }

    /**
     * Get the first instance of a page.
     * @param string $typeSlug slug of the registered page.
     * @return PageData|null
     * @throws Exception if storage driver is not set.
     */
    public static function findFirst(string $typeSlug): ?PageData
    {
        return self::driver()->findFirst($typeSlug);
    }

    /**
     * Save a new instance of a page or update an existing one.
     * @param string $typeSlug slug of the registered page.
     * @param string|null $id unique identifier of the instance.
     * @param array $data
     * @return string unique identifier of the instance.
     * @throws Exception if storage driver is not set.
     */
    public static function save(string $typeSlug, ?string $id, array $data): string
    {
        return self::driver()->save($typeSlug, $id, $data);
    }

    /**
     * Deletes a specific instance of a page.
     * @param string $typeSlug slug of the registered page.
     * @param string $id unique identifier of the instance.
     * @return void
     * @throws Exception if storage driver is not set.
     */
    public static function delete(string $typeSlug, string $id): void
    {
        self::driver()->delete($typeSlug, $id);
    }


    /**
     * Get the storage driver throwing an exception if not set.
     * @return StorageDriver
     * @throws Exception if storage driver is not set.
     */
    private static function driver(): StorageDriver
    {
        if (self::$driver === null) {
            throw new Exception('VanillaCms >> Storage driver not set');
        }
        return self::$driver;
    }
}