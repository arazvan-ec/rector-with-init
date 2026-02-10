<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagHtmlDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
#[CoversClass(BodyTagHtmlDataTransformer::class)]
class BodyTagHtmlDataTransformerTest extends TestCase
{
    private BodyTagHtmlDataTransformer $bodyTagHtmlDataTransformer;

    protected function setUp(): void
    {
        $this->bodyTagHtmlDataTransformer = new BodyTagHtmlDataTransformer();
    }

    #[Test]
    public function canTransformShouldReturnBodyTagHtmlString(): void
    {
        static::assertSame(BodyTagHtml::class, $this->bodyTagHtmlDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $expectedArray = [
            'type' => 'bodytaghtml',
            'content' => 'content',
        ];

        $bodyElementMock = $this->createConfiguredMock(BodyTagHtml::class, $expectedArray);

        $result = $this->bodyTagHtmlDataTransformer->write($bodyElementMock)->read();

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function writeShouldReturnExceptionWhenBodyElementIsNotBodyTagHtml(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BodyElement should be instance of '.BodyTagHtml::class);

        $this->bodyTagHtmlDataTransformer->write($bodyElementMock)->read();
    }
}
