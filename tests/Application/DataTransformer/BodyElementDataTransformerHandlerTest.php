<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer;

use App\Application\DataTransformer\BodyElementDataTransformer;
use App\Application\DataTransformer\BodyElementDataTransformerHandler;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Razvan Alin Munteanu <arazvan@elconfidencial.com>
 */
#[CoversClass(BodyElementDataTransformerHandler::class)]
class BodyElementDataTransformerHandlerTest extends TestCase
{
    private BodyElementDataTransformerHandler $elementDataTransformer;

    /** @var BodyElementDataTransformer|MockObject */
    private BodyElementDataTransformer $dataTransformerMock;

    protected function setUp(): void
    {
        $this->elementDataTransformer = new BodyElementDataTransformerHandler();

        $this->dataTransformerMock = $this->createMock(BodyElementDataTransformer::class);
        $this->elementDataTransformer->addDataTransformer($this->dataTransformerMock);
    }

    #[Test]
    public function executeShouldThrowExceptionWhenDataTransformerNotFound(): void
    {
        $bodyElementMock = $this->createMock(BodyElement::class);
        $bodyElementMock->method('type')
            ->willReturn('bodyTagType');

        $this->dataTransformerMock->method('canTransform')
            ->willReturn(\get_class($bodyElementMock));

        $this->expectExceptionMessage('BodyElement data transformer type bodyTagType not found');
        $this->expectException(BodyDataTransformerNotFoundException::class);
        $this->elementDataTransformer->execute($bodyElementMock);
    }

    #[Test]
    public function executeShouldUseDataTransformerAndReturnArray(): void
    {
        $resolveData = ['data' => 'value'];
        $readResult = [
            'type' => 'paragraph',
            'content' => 'Content',
            'links' => [],
        ];

        $bodyElementMock = $this->createMock(BodyElement::class);

        $this->dataTransformerMock->method('canTransform')
            ->willReturn(\get_class($bodyElementMock));
        $this->dataTransformerMock->method('write')
            ->with($bodyElementMock, $resolveData)
            ->willReturnSelf();
        $this->dataTransformerMock->method('read')
            ->willReturn($readResult);

        $this->elementDataTransformer->addDataTransformer($this->dataTransformerMock);
        $result = $this->elementDataTransformer->execute($bodyElementMock, $resolveData);

        static::assertSame($readResult, $result);
    }
}
