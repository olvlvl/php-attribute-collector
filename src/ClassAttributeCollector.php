<?php

namespace olvlvl\ComposerAttributeCollector;

use Attribute;
use Composer\IO\IOInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;

/**
 * @internal
 */
class ClassAttributeCollector
{
    /**
     * @param bool $ignoreArguments
     *     Attribute arguments aren't used when generating a file that uses reflection.
     *     Setting `$ignoreArguments` to `true` ignores arguments during the attribute collection.
     */
    public function __construct(
        private IOInterface $io,
        private bool $ignoreArguments,
    ) {
    }

    /**
     * @param class-string $class
     *
     * @return array{
     *     array<TransientTargetClass>,
     *     array<TransientTargetMethod>,
     *     array<TransientTargetProperty>,
     * }
     *
     * @throws ReflectionException
     */
    public function collectAttributes(string $class): array
    {
        $classReflection = new ReflectionClass($class);

        if (self::isAttribute($classReflection)) {
            return [ [], [], [] ];
        }

        $classAttributes = [];
        $attributes = $classReflection->getAttributes();

        foreach ($attributes as $attribute) {
            if (self::isAttributeIgnored($attribute)) {
                continue;
            }

            $this->io->debug("Found attribute {$attribute->getName()} on $class");

            $classAttributes[] = new TransientTargetClass(
                $attribute->getName(),
                $this->getArguments($attribute),
            );
        }

        $methodAttributes = [];

        foreach ($classReflection->getMethods() as $methodReflection) {
            foreach ($methodReflection->getAttributes() as $attribute) {
                if (self::isAttributeIgnored($attribute)) {
                    continue;
                }

                $method = $methodReflection->name;

                $this->io->debug("Found attribute {$attribute->getName()} on $class::$method");

                $methodAttributes[] = new TransientTargetMethod(
                    $attribute->getName(),
                    $this->getArguments($attribute),
                    $method,
                );
            }
        }

        $propertyAttributes = [];

        foreach ($classReflection->getProperties() as $propertyReflection) {
            foreach ($propertyReflection->getAttributes() as $attribute) {
                if (self::isAttributeIgnored($attribute)) {
                    continue;
                }

                $property = $propertyReflection->name;
                assert($property !== '');

                $this->io->debug("Found attribute {$attribute->getName()} on $class::$property");

                $propertyAttributes[] = new TransientTargetProperty(
                    $attribute->getName(),
                    $this->getArguments($attribute),
                    $property,
                );
            }
        }

        return [ $classAttributes, $methodAttributes, $propertyAttributes ];
    }

    /**
     * Determines if a class is an attribute.
     *
     * @param ReflectionClass<object> $classReflection
     */
    private static function isAttribute(ReflectionClass $classReflection): bool
    {
        foreach ($classReflection->getAttributes() as $attribute) {
            if ($attribute->getName() === Attribute::class) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ReflectionAttribute<object> $attribute
     */
    private static function isAttributeIgnored(ReflectionAttribute $attribute): bool
    {
        static $ignored = [
            \ReturnTypeWillChange::class => true,
            InheritsAttributes::class => true,
        ];

        return isset($ignored[$attribute->getName()]);
    }

    /**
     * @param ReflectionAttribute<object> $attribute
     *
     * @return array<string, mixed>
     */
    private function getArguments(ReflectionAttribute $attribute): array
    {
        if ($this->ignoreArguments) {
            return [];
        }

        return $attribute->getArguments();
    }
}
