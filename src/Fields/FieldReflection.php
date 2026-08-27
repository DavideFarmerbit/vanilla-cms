<?php

namespace VanillaCms\Fields;

use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

final class FieldReflection
{
    /** @var array<class-string, ReflectionProperty[]> */
    private static array $fieldPropertyCache = [];
    
    /**
     * Get all the initialized, non-null Field values declared on the given object.
     * @return array<string, Field> Name => Field
     */
    public static function getFields(object $object): array
    {
        $fields = [];

        foreach (self::getFieldProperties($object::class) as $property) {
            if (!$property->isInitialized($object)) {
                continue;
            }
            $value = $property->getValue($object);
            if ($value === null) {
                continue; // nullable Field property explicitly set to null
            }
            $fields[$property->getName()] = $value;
        }

        return $fields;
    }

    /**
     * Returns all properties declared as Field or subclass of Field, caching them for future use.
     * @param class-string $class
     * @return ReflectionProperty[]
     * @throws ReflectionException
     */
    private static function getFieldProperties(string $class): array
    {
        // If we've already cached the properties, return them.
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
}
