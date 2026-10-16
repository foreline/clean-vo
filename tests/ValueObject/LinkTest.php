<?php
declare(strict_types=1);

namespace Tests\ValueObject;

use Domain\ValueObject\Link;
use Domain\ValueObject\LinkCollection;
use Domain\ValueObject\Link\Service\BuildLinkUri;
use Domain\ValueObject\Service\ValueObjectData;
use Domain\ValueObject\ShortText;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function testEditorDefaultsKeepDerivedFieldsHiddenWithoutChangingValueRoundTrip(): void
    {
        $config = json_decode(file_get_contents(__DIR__ . '/../../config/crud/vo/Domain/ValueObject/Link.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach (['scheme', 'ip', 'port', 'favicon'] as $part) {
            self::assertSame('hidden', $config['valueObject']['ui']['parts'][$part]['type']);
        }
        self::assertSame('textarea', $config['valueObject']['ui']['parts']['description']['type']);
        $link = new Link(url: new ShortText('example.org'), scheme: new ShortText('https'), ip: new ShortText('192.0.2.1'), port: 443);
        self::assertTrue($link->equals(ValueObjectData::decode(Link::class, ValueObjectData::encode($link))));
    }

    public function testUriUsesAuthorityPortAndSupportsIpv6(): void
    {
        $builder = new BuildLinkUri();
        self::assertSame('https://example.org:8443/path?a=b#c', $builder->build('example.org/path?a=b#c','https','',8443));
        self::assertSame('https://example.org:443/path', $builder->build('https://example.org:443/path','http','',8443));
        self::assertSame('ssh://[2001:db8::1]:22', $builder->build('','ssh','2001:db8::1',22));
        self::assertSame('https://example.org/path', $builder->build('//example.org/path','https'));
        self::assertSame('', $builder->build("java\tscript:alert(1)"));
        self::assertSame('', $builder->build('javascript:alert(1)'));
    }

    public function testCollectionPreservesOrderedDuplicatesAndRoundTrips(): void
    {
        $link = new Link(url:new ShortText('example.org'),name:new ShortText('Example'));
        $collection = (new LinkCollection())->addItem($link)->addItem($link);
        $copy = ValueObjectData::decode(LinkCollection::class,ValueObjectData::encode($collection));
        self::assertCount(2,$copy->getCollection());
        self::assertTrue($link->equals($copy->current()));
        self::assertNotSame($link,$copy->current());
        self::assertTrue($link->equals(ValueObjectData::fromFlat(Link::class,ValueObjectData::flatten($link))));
        $collection->addItems($collection);
        self::assertCount(4,$collection->getCollection());
        $collection->setItems($collection);
        self::assertCount(4,$collection->getCollection());
    }

    public function testLibraryValueCanBeExtended(): void
    {
        $link = new class extends Link {};
        self::assertSame('', $link->getUri());
        self::assertInstanceOf(Link::class,(new LinkCollection())->addItem($link)->current());
    }

    public function testEntityIdentifiersAreNotValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ValueObjectData::decode(LinkCollection::class,[['id'=>1]]);
    }

    public function testInvalidPortIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Link(port:65536);
    }
}
