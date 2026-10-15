<?php
declare(strict_types=1);

namespace Domain\ValueObject\Service;

use BackedEnum;
use Domain\ValueObject\CollectionInterface;
use Domain\ValueObject\FloatValueObjectInterface;
use Domain\ValueObject\IntValueObjectInterface;
use Domain\ValueObject\MixedValueObjectInterface;
use Domain\ValueObject\StringValueObjectInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;

/** Converts typed values at array boundaries. No ORM, entities or I/O. */
class ValueObjectData
{
    /** Flatten constructor values into scalar columns with stable path names. */
    public static function flatten(object $value, string $prefix = 'value'): array
    {
        $data = self::encode($value);
        if (!is_array($data)) { return [$prefix => $data]; }
        $result = [];
        foreach ($data as $key => $part) {
            $column = $prefix . '_' . $key;
            if (is_array($part)) {
                $getter = 'get' . ucfirst($key);
                $result += self::flatten($value->$getter(), $column);
                continue;
            }
            $result[$column] = $part;
        }
        return $result;
    }

    /** @param class-string $className */
    public static function fromFlat(string $className, array $row, string $prefix = 'value'): object
    {
        if (!is_a($className, MixedValueObjectInterface::class, true)) {
            return self::decode($className, $row[$prefix]);
        }
        $data = [];
        foreach ((new ReflectionClass($className))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $key = $prefix . '_' . $parameter->getName();
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && is_a($type->getName(), MixedValueObjectInterface::class, true)) {
                $data[$parameter->getName()] = self::encode(self::fromFlat($type->getName(), $row, $key));
            } elseif (array_key_exists($key, $row)) {
                $data[$parameter->getName()] = $row[$key];
            }
        }
        return self::decode($className, $data);
    }

    public static function encode(?object $value): array|string|int|float|null
    {
        if (null === $value) { return null; }
        if ($value instanceof StringValueObjectInterface) { return (string) $value; }
        if ($value instanceof IntValueObjectInterface) { return $value->__toInteger(); }
        if ($value instanceof FloatValueObjectInterface) { return $value->__toFloat(); }
        if ($value instanceof BackedEnum) { return $value->value; }
        if ($value instanceof CollectionInterface) {
            return array_map(self::encode(...), $value->getCollection() ?? []);
        }
        if (!$value instanceof MixedValueObjectInterface) {
            throw new InvalidArgumentException('Unsupported value type: ' . $value::class);
        }
        $data = [];
        foreach ((new ReflectionClass($value))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            $getter = 'get' . ucfirst($name);
            if (!method_exists($value, $getter)) {
                throw new InvalidArgumentException($value::class . ' requires ' . $getter . '().');
            }
            $part = $value->$getter();
            $data[$name] = is_object($part) ? self::encode($part) : $part;
        }
        return $data;
    }

    /** @param class-string $className */
    public static function decode(string $className, array|string|int|float|bool|null $data): ?object
    {
        if (null === $data) { return null; }
        if (is_a($className, StringValueObjectInterface::class, true)) {
            return new $className(self::scalar('string', $data));
        }
        if (is_a($className, IntValueObjectInterface::class, true)) {
            return new $className(self::scalar('int', $data));
        }
        if (is_a($className, FloatValueObjectInterface::class, true)) {
            return new $className(self::scalar('float', $data));
        }
        if (is_a($className, BackedEnum::class, true)) {
            return $className::from($data);
        }
        if (is_a($className, CollectionInterface::class, true)) {
            if (!is_array($data) || !array_is_list($data)) {
                throw new InvalidArgumentException('A value collection must be a list.');
            }
            $type = (new ReflectionClass($className))->getMethod('current')->getReturnType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new InvalidArgumentException('A collection must declare its current() item type.');
            }
            $collection = new $className();
            foreach ($data as $item) {
                if (null === $item) { throw new InvalidArgumentException('A value collection cannot contain null.'); }
                $collection->addItem(self::decode($type->getName(), $item));
            }
            return $collection;
        }
        if (!is_a($className, MixedValueObjectInterface::class, true) || !is_array($data)) {
            throw new InvalidArgumentException('Expected a structured value for ' . $className);
        }
        $arguments = [];
        $known = [];
        foreach ((new ReflectionClass($className))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $name = $parameter->getName();
            $known[] = $name;
            if (!array_key_exists($name, $data)) {
                if ($parameter->isDefaultValueAvailable()) { continue; }
                throw new InvalidArgumentException('Missing value: ' . $name);
            }
            $type = $parameter->getType();
            if (!$type instanceof ReflectionNamedType) {
                throw new InvalidArgumentException('A value parameter must have one declared type: ' . $name);
            }
            $value = $data[$name];
            if (null === $value && $type->allowsNull()) {
                $arguments[$name] = null;
            } elseif (null === $value) {
                throw new InvalidArgumentException('Null is not allowed: ' . $name);
            } else {
                $arguments[$name] = $type->isBuiltin() ? self::scalar($type->getName(), $value) : self::decode($type->getName(), $value);
            }
        }
        if ([] !== array_diff(array_keys($data), $known)) {
            throw new InvalidArgumentException('Unknown value fields for ' . $className);
        }
        return new $className(...$arguments);
    }

    private static function scalar(string $type, array|string|int|float|bool $value): string|int|float|bool
    {
        if ('string' === $type && is_string($value)) { return $value; }
        if ('int' === $type && (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/D', $value)))) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if (false !== $integer) { return $integer; }
        }
        if ('float' === $type && !is_array($value) && !is_bool($value) && is_numeric($value) && is_finite((float) $value)) { return (float) $value; }
        if ('bool' === $type && in_array($value, [true, false, 0, 1, '0', '1'], true)) { return (bool) $value; }
        throw new InvalidArgumentException('Invalid ' . $type . ' value.');
    }
}
