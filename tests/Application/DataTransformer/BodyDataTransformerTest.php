<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer;

use App\Application\DataTransformer\BodyDataTransformer;
use App\Application\DataTransformer\BodyElementDataTransformerHandler;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
#[CoversClass(BodyDataTransformer::class)]
class BodyDataTransformerTest extends TestCase
{
    private BodyElementDataTransformerHandler&MockObject $bodyElementDataTransformerHandler;
    private LoggerInterface&MockObject $loggerMock;

    private BodyDataTransformer $bodyDataTransformer;

    protected function setUp(): void
    {
        $this->bodyElementDataTransformerHandler = $this->createMock(BodyElementDataTransformerHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->bodyDataTransformer = new BodyDataTransformer(
            $this->bodyElementDataTransformerHandler,
            $this->loggerMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->bodyElementDataTransformerHandler, $this->loggerMock, $this->bodyDataTransformer);
    }

    #[Test]
    public function executeTransformsBodyElementsAndReturnArrayWithElements(): void
    {
        $bodyType = 'elementType';
        $resolveData = ['key' => 'value'];
        $transformedElement = ['transformed' => 'data'];

        $body = $this->createMock(Body::class);
        $body->method('type')->willReturn($bodyType);

        $bodyElement = $this->createMock(BodyElement::class);
        $body->method('getArrayCopy')->willReturn([$bodyElement]);

        $this->bodyElementDataTransformerHandler
            ->method('execute')
            ->with($bodyElement, $resolveData)
            ->willReturn($transformedElement);

        $result = $this->bodyDataTransformer->execute($body, $resolveData);

        static::assertSame(['type' => $bodyType, 'elements' => [$transformedElement]], $result);
    }

    #[Test]
    public function executeSkipsElementsWithoutTransformer(): void
    {
        $bodyType = 'elementType';
        $resolveData = [];

        $bodyElement = $this->createMock(BodyElement::class);

        $body = $this->createMock(Body::class);
        $body->method('type')->willReturn($bodyType);
        $body->method('getArrayCopy')->willReturn([$bodyElement]);

        $this->bodyElementDataTransformerHandler
            ->method('execute')
            ->willThrowException(new BodyDataTransformerNotFoundException('Exception message'));

        $this->loggerMock
            ->expects(static::once())
            ->method('info')
            ->with('Exception message')
            ->willReturnSelf();

        $result = $this->bodyDataTransformer->execute($body, $resolveData);

        static::assertSame(['type' => $bodyType, 'elements' => []], $result);
    }
}
