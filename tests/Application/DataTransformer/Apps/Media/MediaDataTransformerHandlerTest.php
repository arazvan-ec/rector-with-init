<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Media;

use App\Application\DataTransformer\Apps\Media\MediaDataTransformer;
use App\Application\DataTransformer\Apps\Media\MediaDataTransformerHandler;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Editorial\Exceptions\MultimediaDataTransformerNotFoundException;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(MediaDataTransformerHandler::class)]
class MediaDataTransformerHandlerTest extends TestCase
{
    private MediaDataTransformerHandler $mediaDataTransformerHandler;

    /** @var MediaDataTransformer|MockObject */
    private MediaDataTransformer $dataTransformerMock;

    protected function setUp(): void
    {
        $this->mediaDataTransformerHandler = new MediaDataTransformerHandler();

        $this->dataTransformerMock = $this->createMock(MediaDataTransformer::class);
        $this->mediaDataTransformerHandler->addDataTransformer($this->dataTransformerMock);
    }

    #[Test]
    public function executeShouldThrowExceptionWhenDataTransformerNotFound(): void
    {
        $multimediaElement = $this->createMock(MultimediaEmbedVideo::class);
        $multimediaElement->expects($this->once())
            ->method('type')
            ->willReturn('mediaType');

        $opening = $this->createMock(Opening::class);
        $opening->expects($this->once())
            ->method('multimediaId')
            ->willReturn('multimediaId');

        $multimediaOpeningData = [
            'multimediaId' => [
                'opening' => $multimediaElement,
            ],
        ];

        $this->expectExceptionMessage('Media data transformer type mediaType not found');
        $this->expectException(MultimediaDataTransformerNotFoundException::class);
        $this->mediaDataTransformerHandler->execute($multimediaOpeningData, $opening);
    }

    #[Test]
    public function executeShouldUseDataTransformerAndReturnArray(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->expects($this->once())
            ->method('multimediaId')
            ->willReturn('multimediaId');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);

        /** @var array<string, array{opening: MultimediaEmbedVideo}> $multimediaOpeningData */
        $multimediaOpeningData = [
            'multimediaId' => [
                'opening' => $multimedia,
            ],
        ];

        $arrayMultimedia = [
            'id' => 'multimediaId',
            'type' => 'EmbedVideo',
            'caption' => 'Test Caption',
            'embedText' => '<iframe src="https://www.testmotion.com/embed/video/x7u5j5"></iframe>',
        ];

        $this->dataTransformerMock->method('canTransform')
            ->willReturn(\get_class($multimedia));

        $this->dataTransformerMock->expects($this->once())
            ->method('write')
            ->with($multimediaOpeningData, $opening)
            ->willReturnSelf();

        $this->dataTransformerMock->expects($this->once())
            ->method('read')
            ->willReturn($arrayMultimedia);

        $this->mediaDataTransformerHandler->addDataTransformer($this->dataTransformerMock);
        $result = $this->mediaDataTransformerHandler->execute($multimediaOpeningData, $opening);

        $this->assertEquals($arrayMultimedia, $result);
    }
}
