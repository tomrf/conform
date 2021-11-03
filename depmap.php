<?php

use HaydenPierce\ClassFinder\ClassFinder;

require 'vendor/autoload.php';

$map = [];

$classes = ClassFinder::getClassesInNamespace('Tomrf\Snek', ClassFinder::RECURSIVE_MODE);
foreach ($classes as $class) {
    $deps = getClassDependencies($class);
    addToMap($map, $class, $deps);
}

foreach ($map as $class => $deps) {
    echo $class.': ';
    foreach ($deps as $depClass => $dep) {
        echo $depClass.' ';
    }
    echo PHP_EOL;
}

function addToMap(array &$map, string $className, array $classDependencies, $parent = null)
{
    if (!isset($map[$className])) {
        $map[$className] = [];
    }

    foreach ($classDependencies as $key => $value) {
        if ('self' === $key) {
            continue;
        }
        if (is_array($value)) {
            addToMap($map, $className, $value, $key);
        } else {
            if (!is_string($value)) {
                continue;
            }
            $reflectionClass = new ReflectionClass($value);
            if (true === $reflectionClass->isInternal()) {
                continue;
            }
            $map[$className][$value][] = $parent.'-'.$key ?? $key;
        }
    }
}

function getClassDependencies(string $class)
{
    $deps = [];

    $reflection = new ReflectionClass($class);
    $deps['self'] = $reflection->getName();

    if (false !== $reflection->getParentClass()) {
        $deps['parent'] = $reflection->getParentClass()->getName() ?? null;
    } else {
        $deps['parent'] = null;
    }

    $properties = $reflection->getProperties();
    foreach ($properties as $property) {
        $propertyType = $property->getType();
        if (null !== $propertyType && $propertyType instanceof ReflectionNamedType) {
            if (false === $propertyType->isBuiltin()) {
                $propertyTypeName = $propertyType->getName();
                $returnClassReflection = new ReflectionClass($propertyTypeName);
                if (false === $returnClassReflection->isInternal()) {
                    if ($propertyTypeName !== $reflection->getName()) {
                        $deps['properties'][$property->getName()][] = $propertyTypeName;
                    }
                }
            }
        }
    }

    foreach ($reflection->getMethods() as $method) {
        $returnType = $method->getReturnType();
        if (null !== $returnType && $returnType instanceof ReflectionNamedType) {
            if (false === $returnType->isBuiltin()) {
                $returnClassReflection = new ReflectionClass($method->getReturnType()->getName());
                if (false === $returnClassReflection->isInternal()) {
                    if ($method->getReturnType()->getName() !== $reflection->getName()) {
                        $deps['methods'][$method->getName()]['returns'] = $method->getReturnType()->getName();
                    }
                }
            }
        }
        foreach ($method->getParameters() as $parameter) {
            $parameterType = $parameter->getType();
            if (null !== $parameterType && $parameterType instanceof ReflectionNamedType) {
                if (false === $parameterType->isBuiltin()) {
                    $returnClassReflection = new ReflectionClass($parameterType->getName());
                    if (false === $returnClassReflection->isInternal()) {
                        if ($parameterType->getName() !== $reflection->getName()) {
                            $deps['methods'][$method->getName()]['parameters'][] = $parameterType->getName();
                        }
                    }
                }
            }
        }
    }

    return $deps;
}
