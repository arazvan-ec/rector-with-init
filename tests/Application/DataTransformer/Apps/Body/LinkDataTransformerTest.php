<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\LinkDataTransformer;
use Assert\InvalidArgumentException;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Link;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(LinkDataTransformer::class)]
class LinkDataTransformerTest extends TestCase
{
    private LinkDataTransformer $linkDataTransformer;

    protected function setUp(): void
    {
        $this->linkDataTransformer = new LinkDataTransformer();
    }

    #[Test]
    public function canTransformShouldReturnLinkString(): void
    {
        static::assertSame(Link::class, $this->linkDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $expectedArray = [
            'type' => 'link',
            'content' => 'content',
        ];

        $bodyElementMock = $this->createConfiguredMock(Link::class, $expectedArray);

        $result = $this->linkDataTransformer->write($bodyElementMock)->read();

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function writeShouldReturnExceptionWhenBodyElementIsNotLink(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('BodyElement should be instance of '.Link::class);

        $this->linkDataTransformer->write($bodyElementMock)->read();
    }
}
