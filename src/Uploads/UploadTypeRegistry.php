<?php

namespace VanillaCms\Uploads;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Maps file extensions to upload type keys, and type keys to the UploadMeta class handling them.
 * Also holds the allow-list of extensions that may be uploaded.
 */
final class UploadTypeRegistry
{
    /** @var array<string, class-string<UploadMeta>> type key => class */
    private static array $types = [
        'image' => ImageUploadMeta::class,
        'pdf' => PdfUploadMeta::class,
        'generic' => GenericUploadMeta::class,
    ];

    /** @var array<string, string> extension => type key */
    private static array $extensionMap = [
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'gif' => 'image',
        'webp' => 'image',
        'svg' => 'image',
        'pdf' => 'pdf',
    ];

    /** @var string[] */
    private static array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip',
        'mp3', 'mp4', 'mov', 'webm',
    ];

    /**
     * Registers a new upload type, associating it to a set of file extensions.
     * @param string $key unique key identifying the type (e.g. 'image').
     * @param class-string<UploadMeta> $uploadMetaClass
     * @param string[] $extensions file extensions (without the leading dot) handled by this type; also allow-listed.
     */
    public static function registerType(string $key, string $uploadMetaClass, array $extensions): void
    {
        // Check class is an UploadMeta.
        if (!is_subclass_of($uploadMetaClass, UploadMeta::class)) {
            throw new InvalidArgumentException("$uploadMetaClass must extend UploadMeta");
        }

        // Get the constructor from reflection.
        try {
            $reflection = new ReflectionClass($uploadMetaClass);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException("$uploadMetaClass does not exist");
        }
        $constructor = $reflection->getConstructor();

        // Check class has zero params constructor.
        if ($constructor !== null && $constructor->getNumberOfParameters() > 0) {
            throw new InvalidArgumentException(
                "$uploadMetaClass must have a constructor with no parameters"
            );
        }

        // Check class is instantiable.
        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException("$uploadMetaClass must be instantiable (not abstract/interface)");
        }

        self::$types[$key] = $uploadMetaClass;

        foreach ($extensions as $extension) {
            $extension = strtolower($extension);
            self::$extensionMap[$extension] = $key;
            if (!in_array($extension, self::$allowedExtensions, true)) {
                self::$allowedExtensions[] = $extension;
            }
        }
    }

    /**
     * Returns the type key for a given extension, defaulting to 'generic' if not explicitly registered.
     * @param string $extension
     * @return string
     */
    public static function typeForExtension(string $extension): string
    {
        return self::$extensionMap[strtolower($extension)] ?? 'generic';
    }

    /**
     * Returns the UploadMeta class handling a given type key, defaulting to GenericUploadMeta.
     * @param string $type
     * @return class-string<UploadMeta>
     */
    public static function classForType(string $type): string
    {
        return self::$types[$type] ?? GenericUploadMeta::class;
    }

    /**
     * Returns all registered type keys.
     * @return string[]
     */
    public static function types(): array
    {
        return array_keys(self::$types);
    }

    /**
     * Returns true if a file with the given extension is allowed to be uploaded.
     * @param string $extension
     * @return bool
     */
    public static function isExtensionAllowed(string $extension): bool
    {
        return in_array(strtolower($extension), self::$allowedExtensions, true);
    }
}
