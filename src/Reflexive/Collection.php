<?php

namespace olvlvl\ComposerAttributeCollector\Reflexive;

use olvlvl\ComposerAttributeCollector\Collection as CollectionAbstract;
use olvlvl\ComposerAttributeCollector\TargetClass;
use olvlvl\ComposerAttributeCollector\TargetMethod;
use olvlvl\ComposerAttributeCollector\TargetProperty;
use ReflectionClass;
use ReflectionException;

/**
 * @readonly
 * @internal
 */
class Collection extends CollectionAbstract
{
    /**
     * @param array<class-string, array{
     *     classes?: array<class-string>,
     *     methods?: array<array{ class-string, non-empty-string }>,
     *     properties?: array<array{ class-string, non-empty-string }>
     * }> $attributes
     */
    public function __construct(
        private array $attributes,
    ) {
    }

    /**
     * @inheritdoc
     * @throws ReflectionException
     */
    public function findTargetClasses(string $attribute): array
    {
        $t = [];

        foreach ($this->attributes[$attribute]['classes'] ?? [] as $class) {
            $t[] = $this->createTargetClasses($attribute, $class);
        }

        return array_merge(...$t);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attribute
     * @param class-string $class
     *
     * @return array<TargetClass<T>>
     * @throws ReflectionException
     */
    private function createTargetClasses(string $attribute, string $class): array
    {
        $t = [];
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getAttributes($attribute) as $reflectionAttribute) {
            $t[] = new TargetClass($reflectionAttribute->newInstance(), $class);
        }

        return $t;
    }

    /**
     * @inheritdoc
     * @throws ReflectionException
     */
    public function findTargetMethods(string $attribute): array
    {
        $t = [];

        foreach ($this->attributes[$attribute]['methods'] ?? [] as [ $class, $method ]) {
            $t[] = $this->createTargetMethods($attribute, $class, $method);
        }

        return array_merge(...$t);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attribute
     * @param class-string $class
     * @param non-empty-string $method
     *
     * @return array<TargetMethod<T>>
     * @throws ReflectionException
     */
    private function createTargetMethods(string $attribute, string $class, string $method): array
    {
        $t = [];

        $reflection = new \ReflectionMethod($class, $method);

        foreach ($reflection->getAttributes($attribute) as $reflectionAttribute) {
            $t[] = new TargetMethod($reflectionAttribute->newInstance(), $class, $method);
        }

        return $t;
    }

    /**
     * @inheritdoc
     * @throws ReflectionException
     */
    public function findTargetProperties(string $attribute): array
    {
        $t = [];

        foreach ($this->attributes[$attribute]['properties'] ?? [] as [ $class, $property ]) {
            $t[] = $this->createTargetProperties($attribute, $class, $property);
        }

        return array_merge(...$t);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attribute
     * @param class-string $class
     * @param non-empty-string $property
     *
     * @return array<TargetProperty<T>>
     * @throws ReflectionException
     */
    private function createTargetProperties(string $attribute, string $class, string $property): array
    {
        $t = [];

        $reflection = new \ReflectionProperty($class, $property);

        foreach ($reflection->getAttributes($attribute) as $reflectionAttribute) {
            $t[] = new TargetProperty($reflectionAttribute->newInstance(), $class, $property);
        }

        return $t;
    }

    /**
     * @inheritdoc
     * @throws ReflectionException
     */
    public function filterTargetClasses(callable $predicate): array
    {
        $t = [];

        foreach (array_keys($this->attributes) as $attribute) {
            foreach ($this->attributes[$attribute]['classes'] ?? [] as $class) {
                if ($predicate($attribute, $class)) {
                    $t[] = $this->createTargetClasses($attribute, $class);
                }
            }
        }

        return array_merge(...$t);
    }

    /**
     * @inheritdoc
     * @throws ReflectionException
     */
    public function filterTargetMethods(callable $predicate): array
    {
        $t = [];

        foreach (array_keys($this->attributes) as $attribute) {
            foreach ($this->attributes[$attribute]['methods'] ?? [] as [ $class, $method ]) {
                if ($predicate($attribute, $class, $method)) {
                    $t[] = $this->createTargetMethods($attribute, $class, $method);
                }
            }
        }

        return array_merge(...$t);
    }

    /**
     * @inheritdoc
     * @throws ReflectionException
     */
    public function filterTargetProperties(callable $predicate): array
    {
        $t = [];

        foreach (array_keys($this->attributes) as $attribute) {
            foreach ($this->attributes[$attribute]['properties'] ?? [] as [ $class, $properties ]) {
                if ($predicate($attribute, $class, $properties)) {
                    $t[] = $this->createTargetProperties($attribute, $class, $properties);
                }
            }
        }

        return array_merge(...$t);
    }
}
