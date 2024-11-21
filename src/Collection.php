<?php

namespace olvlvl\ComposerAttributeCollector;

abstract class Collection
{
    /**
     * @template T of object
     *
     * @param class-string<T> $attribute
     *
     * @return array<TargetClass<T>>
     */
    abstract public function findTargetClasses(string $attribute): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $attribute
     *
     * @return array<TargetMethod<T>>
     */
    abstract public function findTargetMethods(string $attribute): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $attribute
     *
     * @return array<TargetProperty<T>>
     */
    abstract public function findTargetProperties(string $attribute): array;

    /**
     * @param callable(class-string $attribute, class-string $class):bool $predicate
     *
     * @return array<TargetClass<object>>
     */
    abstract public function filterTargetClasses(callable $predicate): array;

    /**
     * @param callable(class-string $attribute, class-string $class, non-empty-string $method):bool $predicate
     *
     * @return array<TargetMethod<object>>
     */
    abstract public function filterTargetMethods(callable $predicate): array;

    /**
     * @param callable(class-string $attribute, class-string $class, non-empty-string $property):bool $predicate
     *
     * @return array<TargetProperty<object>>
     */
    abstract public function filterTargetProperties(callable $predicate): array;

    /**
     * @param class-string $class
     */
    public function forClass(string $class): ForClass
    {
        $classAttributes = [];

        foreach ($this->filterTargetClasses(fn($a, $c): bool => $c === $class) as $targetClass) {
            $classAttributes[] = $targetClass->attribute;
        }

        $methodAttributes = [];

        foreach ($this->filterTargetMethods(fn($a, $c): bool => $c === $class) as $targetMethod) {
            $methodAttributes[$targetMethod->name][] = $targetMethod->attribute;
        }

        $propertyAttributes = [];

        foreach ($this->filterTargetProperties(fn($a, $c): bool => $c === $class) as $targetProperty) {
            $propertyAttributes[$targetProperty->name][] = $targetProperty->attribute;
        }

        return new ForClass(
            $classAttributes,
            $methodAttributes,
            $propertyAttributes,
        );
    }
}
