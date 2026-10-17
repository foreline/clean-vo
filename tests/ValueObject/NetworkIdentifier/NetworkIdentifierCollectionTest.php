<?php
declare(strict_types=1);

namespace Tests\ValueObject\NetworkIdentifier;

use ArrayIterator;
use Domain\ValueObject\NetworkIdentifier\Domain;
use Domain\ValueObject\NetworkIdentifier\DomainCollection;
use Domain\ValueObject\NetworkIdentifier\Email;
use Domain\ValueObject\NetworkIdentifier\EmailCollection;
use Domain\ValueObject\NetworkIdentifier\MacAddressCollection;
use Domain\ValueObject\NetworkIdentifier\URICollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Проверяет общий контракт сетевых коллекций с настоящим CollectionTrait. */
final class NetworkIdentifierCollectionTest extends TestCase
{
    /** Наборы значений для каждого поддерживаемого типа коллекции. */
    public static function collections(): array
    {
        return [
            'email' => [EmailCollection::class, ['first@example.com', 'second@example.org']],
            'mac' => [MacAddressCollection::class, ['00:11:22:33:44:55', '00:11:22:33:44:66']],
            'uri' => [URICollection::class, ['https://example.com/a', 'https://example.org/b']],
            'domain' => [DomainCollection::class, ['example.com', 'example.org']],
        ];
    }

    /**
     * Пустая коллекция загружается и получает состояние из конструктора трейта.
     * @dataProvider collections
     */
    public function testEmptyCollection(string $class, array $strings): void
    {
        $collection = new $class();
        self::assertSame([], $collection->getCollection());
        self::assertSame([], $collection->toStrings());
        self::assertSame(0, $collection->getCount());
        self::assertSame(0, $collection->key());
        self::assertFalse($collection->valid());
        self::assertNull($collection->current());
        self::assertNull($collection->previous());
        self::assertSame([], iterator_to_array($collection));
    }

    /**
     * Фабрика, обход, фильтрация и добавление сохраняют тип и порядок элементов.
     * @dataProvider collections
     */
    public function testCollectionOperations(string $class, array $strings): void
    {
        $collection = $class::fromStrings($strings);
        $items = $collection->getCollection();
        self::assertSame($strings, $collection->toStrings());
        self::assertSame(2, $collection->getCount());
        self::assertSame($items[0], $collection->current());
        self::assertSame($collection, $collection->next());
        self::assertSame($items[1], $collection->current());
        self::assertSame($items[0], $collection->previous());
        self::assertSame($items, iterator_to_array($collection));
        self::assertSame($collection, $collection->rewind());
        self::assertTrue($collection->has($items[0]));
        self::assertSame([$strings[1]], $collection->filter(fn ($item) => $item === $items[1])->toStrings());
        $copy = new $class();
        self::assertSame($copy, $copy->addItems($collection));
        self::assertSame($strings, $copy->toStrings());
    }

    /**
     * null очищает коллекцию и сбрасывает курсор, после чего можно добавлять элементы.
     * @dataProvider collections
     */
    public function testClearAndReuse(string $class, array $strings): void
    {
        $collection = $class::fromStrings($strings);
        $first = $collection->current();
        $collection->next();
        self::assertSame($collection, $collection->setItems(null));
        self::assertSame([], $collection->getCollection());
        self::assertSame(0, $collection->getCount());
        self::assertSame(0, $collection->key());
        self::assertNull($collection->current());
        self::assertSame($collection, $collection->addItem($first));
        self::assertSame($first, $collection->current());
    }

    /**
     * Замена поддерживает Iterator и саму коллекцию, не теряя элементы.
     * @dataProvider collections
     */
    public function testReplacementAndSelfAssignment(string $class, array $strings): void
    {
        $collection = $class::fromStrings($strings);
        $collection->next();
        self::assertSame($collection, $collection->setItems($collection));
        self::assertSame($strings, $collection->toStrings());
        self::assertSame(0, $collection->key());
        $second = $collection->getCollection()[1];
        $collection->next();
        self::assertSame($collection, $collection->setItems(new ArrayIterator([$second])));
        self::assertSame([$strings[1]], $collection->toStrings());
        self::assertSame($second, $collection->current());
    }

    /**
     * Добавление коллекции к самой себе удваивает исходные элементы один раз.
     * @dataProvider collections
     */
    public function testSelfAppend(string $class, array $strings): void
    {
        $collection = $class::fromStrings($strings);
        self::assertSame($collection, $collection->addItems($collection));
        self::assertSame(array_merge($strings, $strings), $collection->toStrings());
    }

    /**
     * Чужой ValueObject отклоняется без изменения коллекции.
     * @dataProvider collections
     */
    public function testInvalidItem(string $class, array $strings): void
    {
        $collection = $class::fromStrings($strings);
        try {
            $collection->addItem(EmailCollection::class === $class ? new Domain('example.net') : new Email('wrong@example.net'));
            self::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($strings, $collection->toStrings());
        }
    }

    /**
     * Неудачная замена не оставляет частично обновлённое состояние.
     * @dataProvider collections
     */
    public function testInvalidReplacementPreservesState(string $class, array $strings): void
    {
        $collection = $class::fromStrings($strings);
        $first = $collection->current();
        $collection->next();
        try {
            $wrong = EmailCollection::class === $class ? new Domain('example.net') : new Email('wrong@example.net');
            $collection->setItems(new ArrayIterator([$first, $wrong]));
            self::fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $exception) {
            self::assertSame($strings, $collection->toStrings());
            self::assertSame(1, $collection->key());
        }
    }
}
