<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagSummaryDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
#[CoversClass(BodyTagSummaryDataTransformer::class)]
class BodyTagSummaryDataTransformerTest extends TestCase
{
    private BodyTagSummaryDataTransformer $bodyTagSummaryDataTransformer;

    protected function setUp(): void
    {
        $this->bodyTagSummaryDataTransformer = new BodyTagSummaryDataTransformer();
    }

    #[Test]
    public function canTransformShouldReturnBodyTagSummaryString(): void
    {
        static::assertSame(BodyTagSummary::class, $this->bodyTagSummaryDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $expectedArray = [
            'type' => 'bodytagsummary',
            'content' => 'content',
        ];

        $bodyElementMock = $this->createConfiguredMock(BodyTagSummary::class, $expectedArray);

        $result = $this->bodyTagSummaryDataTransformer->write($bodyElementMock)->read();

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function writeShouldReturnExceptionWhenBodyElementIsNotBodyTagSummary(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BodyElement should be instance of '.BodyTagSummary::class);

        $this->bodyTagSummaryDataTransformer->write($bodyElementMock)->read();
    }
}
