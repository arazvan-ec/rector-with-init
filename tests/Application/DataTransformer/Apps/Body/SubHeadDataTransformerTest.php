<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\ElementContentDataTransformer;
use App\Application\DataTransformer\Apps\Body\ElementContentWithLinksDataTransformer;
use App\Application\DataTransformer\Apps\Body\ElementTypeDataTransformer;
use App\Application\DataTransformer\Apps\Body\SubHeadDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\SubHead;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(SubHeadDataTransformer::class)]
#[CoversClass(ElementContentWithLinksDataTransformer::class)]
#[CoversClass(ElementContentDataTransformer::class)]
#[CoversClass(ElementTypeDataTransformer::class)]
class SubHeadDataTransformerTest extends TestCase
{
    private SubHeadDataTransformer $subHeadDataTransformer;

    protected function setUp(): void
    {
        $this->subHeadDataTransformer = new SubHeadDataTransformer();
    }

    #[Test]
    public function canTransformShouldReturnSubHeadString(): void
    {
        static::assertSame(SubHead::class, $this->subHeadDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $expectedLink = [
            'type' => 'link',
            'content' => 'links',
            'url' => 'https://www.elconfidencial.com/',
            'target' => '_self',
        ];

        $linkMock = $this->createConfiguredMock(Link::class, $expectedLink);

        $expectedArray = [
            'type' => 'subhead',
            'content' => 'Contenido #1, con links',
            'links' => [$linkMock],
        ];

        $bodyElementMock = $this->createConfiguredMock(SubHead::class, $expectedArray);

        $result = $this->subHeadDataTransformer->write($bodyElementMock)->read();

        $expectedArray['links'] = [$expectedLink];

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function readShouldReturnExpectedArrayWithEmptyLinks(): void
    {
        $expectedArray = [
            'type' => 'subhead',
            'content' => 'Contenido #1, sin links',
            'links' => [],
        ];

        $bodyElementMock = $this->createConfiguredMock(SubHead::class, $expectedArray);

        $expectedArray['links'] = null;

        $result = $this->subHeadDataTransformer->write($bodyElementMock)->read();

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function writeShouldReturnExceptionWhenBodyElementIsNotSubHead(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BodyElement should be instance of '.SubHead::class);

        $this->subHeadDataTransformer->write($bodyElementMock)->read();
    }
}
