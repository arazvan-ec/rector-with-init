<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\GenericListDataTransformer;
use App\Application\DataTransformer\Apps\Body\NumberedListDataTransformer;
use App\Tests\ArrayIteratorTrait;
use Assert\InvalidArgumentException;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\ListItem;
use Ec\Editorial\Domain\Model\Body\NumberedList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(NumberedListDataTransformer::class)]
#[CoversClass(GenericListDataTransformer::class)]
class NumberedListDataTransformerTest extends TestCase
{
    use ArrayIteratorTrait;

    private NumberedListDataTransformer $numberedListTransformer;

    protected function setUp(): void
    {
        $this->numberedListTransformer = new NumberedListDataTransformer();
    }

    protected function tearDown(): void
    {
        unset($this->numberedListTransformer);
    }

    #[Test]
    public function canTransformShouldReturnNumberedListString(): void
    {
        static::assertSame(NumberedList::class, $this->numberedListTransformer->canTransform());
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
            'content' => 'List item con links',
            'links' => [$linkMock],
        ];

        $listItemMock = $this->createConfiguredMock(ListItem::class, $expectedListItem);

        $expectedArray = [
            'type' => 'numberedlist',
        ];

        $bodyElementMock = $this->createConfiguredMock(NumberedList::class, $expectedArray);
        $this->configureArrayIteratorMock($listItemMock, $bodyElementMock);

        $result = $this->numberedListTransformer->write($bodyElementMock)->read();

        $expectedListItem['links'] = [$expectedLink];
        $expectedArray['items'] = [$expectedListItem];

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function readShouldReturnExpectedArrayWithEmptyLinks(): void
    {
        $expectedListItem = [
            'type' => 'listitem',
            'content' => 'List item con #replace0#',
            'links' => [],
        ];

        $listItemMock = $this->createConfiguredMock(ListItem::class, $expectedListItem);

        $expectedArray = [
            'type' => 'numberedlist',
        ];

        $bodyElementMock = $this->createConfiguredMock(NumberedList::class, $expectedArray);
        $this->configureArrayIteratorMock($listItemMock, $bodyElementMock);

        $result = $this->numberedListTransformer->write($bodyElementMock)->read();

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
                NumberedList::class
            )
        );

        $this->numberedListTransformer->write($bodyElementMock)->read();
    }
}
