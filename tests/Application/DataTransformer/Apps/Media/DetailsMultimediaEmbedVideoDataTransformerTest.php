<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Media;

use App\Application\DataTransformer\Apps\Media\DataTransformers\DetailsMultimediaEmbedVideoDataTransformer;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailsMultimediaEmbedVideoDataTransformerTest extends TestCase
{
    private DetailsMultimediaEmbedVideoDataTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DetailsMultimediaEmbedVideoDataTransformer();
    }

    #[Test]
    public function readShouldReturnsDefaultForEmptyMultimedia(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->expects($this->once())
            ->method('multimediaId')
            ->willReturn('');

        $result = $this->transformer->write([], $opening)->read();

        static::assertEquals([], $result);
    }

    #[Test]
    public function readShouldReturnsDefaultForNonExistentMultimediaId(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->expects($this->once())
            ->method('multimediaId')
            ->willReturn('nonExistentId');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);

        /** @var array<string, array{opening: MultimediaEmbedVideo}>|array{} $arrayMultimedia */
        $arrayMultimedia = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->write($arrayMultimedia, $opening)->read();

        static::assertEquals([], $result);
    }

    #[Test]
    public function readShouldReturnsEmbedVideoDefaultDataForValidMultimedia(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening
            ->expects($this->once())
            ->method('multimediaId')
            ->willReturn('id1');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia
            ->expects($this->once())
            ->method('caption')
            ->willReturn('Test Caption');
        $multimedia
            ->expects($this->exactly(2))
            ->method('html')
            ->willReturn('<iframe src="https://www.testmotion.com/embed/video/x7u5j5"></iframe>');

        /** @var array<string, array{opening: MultimediaEmbedVideo}> $arrayMultimedia */
        $arrayMultimedia = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->write($arrayMultimedia, $opening)->read();

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('id1', $result['id']);
        $this->assertSame('embedVideo', $result['type']);
        $this->assertSame('Test Caption', $result['caption']);
        $this->assertSame('<iframe src="https://www.testmotion.com/embed/video/x7u5j5"></iframe>', $result['html']);
    }

    #[Test]
    public function readShouldReturnsEmbedVideoDailymotionDataForValidMultimedia(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening
            ->expects($this->once())
            ->method('multimediaId')
            ->willReturn('id1');

        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia
            ->expects($this->once())
            ->method('caption')
            ->willReturn('Test Caption');
        $multimedia
            ->expects($this->exactly(2))
            ->method('html')
            ->willReturn('<div itemscope itemtype="https://schema.org/VideoObject"><meta itemprop="name" content="prueba"><meta itemprop="description" content="asdasda"><meta itemprop="uploadDate" content="2025-08-29T12:00:16.000Z"><meta itemprop="thumbnailUrl" content="https://s2.dmcdn.net/v/Z0MUI1ekN8LWSfu6D/x180"><meta itemprop="duration" content="P5S"><meta itemprop="embedUrl" content="https://geo.dailymotion.com/player/x1i0xw.html?video=x9pnrf6"><script src="https://geo.dailymotion.com/player/x1i0xw.js" data-video="x9pnrf6"></script></div>');

        /** @var array<string, array{opening: MultimediaEmbedVideo}> $arrayMultimedia */
        $arrayMultimedia = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->write($arrayMultimedia, $opening)->read();

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('id1', $result['id']);
        $this->assertSame('embedVideoDailyMotion', $result['type']);
        $this->assertSame('Test Caption', $result['caption']);
        $this->assertSame('x1i0xw', $result['playerId']);
        $this->assertSame('x9pnrf6', $result['videoId']);
    }

    #[Test]
    public function canTransformShouldReturnEmbedVideoClass(): void
    {
        $this->assertSame(MultimediaEmbedVideo::class, $this->transformer->canTransform());
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenPregMatchFails(): void
    {
        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia->method('html')->willReturn('some invalid html');

        $method = new \ReflectionMethod($this->transformer, 'extractDailyMotionData');
        $method->setAccessible(true);

        $result = $method->invoke($this->transformer, $multimedia);

        static::assertSame([], $result, 'Should return empty array when preg_match fails');
    }

    #[Test]
    public function shouldReturnEmptyArrayWhenMatchesAreMissing(): void
    {
        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $htmlContent = '<div>Some HTML but no expected player and video id</div>';
        $multimedia->method('html')->willReturn($htmlContent);

        $method = new \ReflectionMethod($this->transformer, 'extractDailyMotionData');
        $method->setAccessible(true);

        $result = $method->invoke($this->transformer, $multimedia);

        static::assertSame([], $result, 'Should return empty array when matches are missing');
    }

    #[Test]
    public function shouldReturnPlayerIdAndVideoIdWhenMatchesExist(): void
    {
        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $htmlContent = '<iframe src="https://geo.dailymotion.com/player/x1i0xw.html?video=x9pnrf6"></iframe>';
        $multimedia->method('html')->willReturn($htmlContent);

        $method = new \ReflectionMethod($this->transformer, 'extractDailyMotionData');
        $method->setAccessible(true);

        /** @var string[] $result */
        $result = $method->invoke($this->transformer, $multimedia);
        static::assertArrayHasKey('playerId', $result);
        static::assertArrayHasKey('videoId', $result);
        static::assertNotEmpty($result['playerId']);
        static::assertNotEmpty($result['videoId']);
    }

    #[Test]
    public function buildDailyMotionResponseShouldReturnEmptyArrayForInvalidData(): void
    {
        $multimedia = $this->createMock(MultimediaEmbedVideo::class);
        $multimedia->method('html')->willReturn('some invalid html');

        $method = new \ReflectionMethod($this->transformer, 'buildDailyMotionResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->transformer, 'id1', $multimedia);

        static::assertSame([], $result, 'Should return empty array for invalid DailyMotion data');
    }

    public function tearDown(): void
    {
        unset($this->transformer);
    }
}
