<?php

namespace VanillaCms\Core\Registry;

use Closure;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use VanillaCms\Fields\Field;
use VanillaCms\Storage\PageData;

class Page
{
    /** @var array<class-string, ReflectionProperty[]> */
    private static array $fieldPropertyCache = [];
    
    private string $slug;
    private string $label;
    private bool $isArchetype = false;

    private string $path;
    private Closure $urlBuilder;
    
    public function __construct(string $slug, string $label, string $path, bool $isArchetype, ?callable $urlBuilder = null)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->isArchetype = $isArchetype;

        $this->path = $path;
        $this->urlBuilder = $urlBuilder ?? (!$isArchetype 
            ? (fn () => '/' . $slug) 
            : (fn (PageData $data) => '/' . $slug . '/' . $data->slug)
        );
    }
    
    public function slug(): string
    {
        return $this->slug;
    }
    
    public function label(): string
    {
        return $this->label;
    }
    
    public function path(): string
    {
        return $this->path;
    }
    
    public function url(PageData $data): string
    {
        return ($this->urlBuilder)($data);
    }
    
    public function isArchetype(): bool {
        return $this->isArchetype;
    }
    
    /*================================================================================================================*/
    // Fields Reflection

    /** 
     * Get all the Fields of this class.
     * @return Field[]
     */
    public function getFields(): array
    {
        $fields = [];

        foreach (self::getFieldProperties() as $property) {
            if (!$property->isInitialized($this)) {
                continue;
            }
            $value = $property->getValue($this);
            if ($value === null) {
                continue; // nullable Field property explicitly set to null
            }
            $fields[$property->getName()] = $value;
        }

        return $fields;
    }
    
    /**
     * Returns all properties declared as Field or subclass of Field, caching them for future use.
     * @return ReflectionProperty[]
     */
    private static function getFieldProperties(): array
    {
        $class = static::class;

        // If we've already cached the properties, return them.'
        if (isset(self::$fieldPropertyCache[$class])) {
            return self::$fieldPropertyCache[$class];
        }

        $reflection = new ReflectionClass($class);
        $fieldProps = [];

        // Iterate through all the properties and use their Type to determine if they're a Field.
        foreach ($reflection->getProperties() as $property) {
            $type = $property->getType();

            // If the property is a Field or a subclass of Field, add it to the list.
            if (self::typeIsFieldOrSubclass($type)) {
                $fieldProps[] = $property;
            }
        }

        // Cache the properties and return them.
        return self::$fieldPropertyCache[$class] = $fieldProps;
    }

    /**
     * Returns true if the given type is a Field or a subclass of Field.
     * @param ReflectionType|null $type
     * @return bool
     */
    private static function typeIsFieldOrSubclass(?ReflectionType $type): bool
    {
        // untyped property — can't tell without a value, so excluded
        if ($type === null) {
            return false;
        }

        // Handle union types (e.g. Field|null written as a union, not just ?Field)
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $inner) {
                if (self::typeIsFieldOrSubclass($inner)) {
                    return true;
                }
            }
            return false;
        }

        // a property can't be "a Field AND something else" meaningfully here
        if ($type instanceof ReflectionIntersectionType) {
            return false;
        }

        // Make sure this can actually be a Field (NOT a built-in type like int + actually a NamedType)
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        // Return true if the type is a Field or a subclass of Field
        $typeName = $type->getName();
        return $typeName === Field::class || is_subclass_of($typeName, Field::class);
    }

    // ~Fields Reflection
    /*================================================================================================================*/
}