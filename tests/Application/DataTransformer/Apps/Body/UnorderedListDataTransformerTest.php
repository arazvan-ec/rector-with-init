<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\GenericListDataTransformer;
use App\Application\DataTransformer\Apps\Body\UnorderedListDataTransformer;
use App\Tests\ArrayIteratorTrait;
use Assert\InvalidArgumentException;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\ListItem;
use Ec\Editorial\Domain\Model\Body\UnorderedList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(UnorderedListDataTransformer::class)]
#[CoversClass(GenericListDataTransformer::class)]
class UnorderedListDataTransformerTest extends TestCase
{
    use ArrayIteratorTrait;

    private UnorderedListDataTransformer $unorderedListDataTransformer;

    protected function setUp(): void
    {
        $this->unorderedListDataTransformer = new UnorderedListDataTransformer();
    }

    #[Test]
    public function canTransformShouldReturnGenericListString(): void
    {
        static::assertSame(UnorderedList::class, $this->unorderedListDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $expectedLink = [
            'type' => 'link',
            'content' => 'link',
            'url' => 'https://www.elconfidencial.com/',
            'target' => '_self',
        ];

        $linkMock = $this->createConfiguredMock(Link::class, $expectedLink);
        $expectedListItem = [
            'type' => 'listitem',
            'content' => 'List item con #replace0#',
            'links' => [$linkMock],
        ];

        $listItemMock = $this->createConfiguredMock(ListItem::class, $expectedListItem);

        $expectedArray = [
            'type' => 'unorderedlist',
        ];

        $bodyElementMock = $this->createConfiguredMock(UnorderedList::class, $expectedArray);
        $this->configureArrayIteratorMock($listItemMock, $bodyElementMock);

        $result = $this->unorderedListDataTransformer->write($bodyElementMock)->read();

        $expectedListItem['links'] = [$expectedLink];
        $expectedArray['items'] = [$expectedListItem];

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function readShouldReturnExpectedArrayWithEmptyLinks(): void
    {
        $expectedListItem = [
            'type' => 'listitem',
            'content' => 'List item con links',
            'links' => [],
        ];

        $listItemMock = $this->createConfiguredMock(ListItem::class, $expectedListItem);

        $expectedArray = [
            'type' => 'unorderedlist',
        ];

        $bodyElementMock = $this->createConfiguredMock(UnorderedList::class, $expectedArray);
        $this->configureArrayIteratorMock($listItemMock, $bodyElementMock);

        $result = $this->unorderedListDataTransformer->write($bodyElementMock)->read();

        $expectedListItem['links'] = null;
        $expectedArray['items'] = [$expectedListItem];

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function writeShouldReturnExceptionWhenBodyElementIsNotUnorderedList(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Class "%s" was expected to be instanceof of "%s" but is not.',
                \get_class($bodyElementMock),
                UnorderedList::class
            )
        );

        $this->unorderedListDataTransformer->write($bodyElementMock)->read();
    }
}
