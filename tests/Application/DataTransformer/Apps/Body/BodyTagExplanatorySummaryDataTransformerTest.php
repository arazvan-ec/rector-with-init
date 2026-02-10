<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagExplanatorySummaryDataTransformer;
use App\Application\DataTransformer\BodyDataTransformerInterface;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyNormal;
use Ec\Editorial\Domain\Model\Body\BodyTagExplanatorySummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
#[CoversClass(BodyTagExplanatorySummaryDataTransformer::class)]
class BodyTagExplanatorySummaryDataTransformerTest extends TestCase
{
    private BodyTagExplanatorySummaryDataTransformer $bodyTagExplanatorySummaryDataTransformer;
    /** @var BodyDataTransformerInterface|MockObject */
    private BodyDataTransformerInterface $bodyDataTransformer;

    protected function setUp(): void
    {
        $this->bodyDataTransformer = $this->createMock(BodyDataTransformerInterface::class);
        $this->bodyTagExplanatorySummaryDataTransformer = new BodyTagExplanatorySummaryDataTransformer(
            $this->bodyDataTransformer
        );
    }

    #[Test]
    public function canTransformShouldReturnBodyTagSummaryString(): void
    {
        static::assertSame(BodyTagExplanatorySummary::class, $this->bodyTagExplanatorySummaryDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $expectedArray = [
            'type' => 'bodytagexplanatorysummary',
            'title' => 'this is a title for bodytagexplanatorysummary',
            'items' => [],
        ];

        $bodyElementMock = $this->createMock(BodyTagExplanatorySummary::class);
        $bodyNormalMock = $this->createMock(BodyNormal::class);

        $bodyElementMock->expects(static::once())
            ->method('body')
            ->willReturn($bodyNormalMock);

        $this->bodyDataTransformer->expects(static::once())
            ->method('execute')
            ->with($bodyNormalMock, [])
            ->willReturn([
                'type' => '',
                'elements' => [],
            ]);

        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytagexplanatorysummary');

        $bodyElementMock->expects(static::once())
            ->method('title')
            ->willReturn('this is a title for bodytagexplanatorysummary');

        $result = $this->bodyTagExplanatorySummaryDataTransformer->write($bodyElementMock)->read();

        static::assertSame($expectedArray, $result);
    }

    #[Test]
    public function writeShouldReturnExceptionWhenBodyElementIsNotBodyTagSummary(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BodyElement should be instance of '.BodyTagExplanatorySummary::class);

        $this->bodyTagExplanatorySummaryDataTransformer->write($bodyElementMock)->read();
    }
}
