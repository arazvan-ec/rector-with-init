<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Media;

use App\Application\DataTransformer\Apps\Media\DataTransformers\DetailsMultimediaPhotoDataTransformer;
use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\Clipping;
use Ec\Multimedia\Domain\Model\Multimedia\Clippings;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
class DetailsMultimediaPhotoDataTransformerTest extends TestCase
{
    private DetailsMultimediaPhotoDataTransformer $transformer;

    /** @var MockObject|Thumbor */
    private Thumbor|MockObject $thumbor;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->transformer = new DetailsMultimediaPhotoDataTransformer($this->thumbor);
    }

    #[Test]
    public function shouldReadReturnsDefaultForEmptyMultimedia(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('');

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

        $multimedia = $this->createMock(MultimediaPhoto::class);

        /** @var array<string, array{opening: MultimediaPhoto}>|array{} $arrayMultimedia */
        $arrayMultimedia = [
            'id1' => [
                'opening' => $multimedia,
            ],
        ];

        $result = $this->transformer->write($arrayMultimedia, $opening)->read();

        static::assertEquals([], $result);
    }

    #[Test]
    public function shouldReadReturnsDefaultIfNoResourceForId(): void
    {
        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('abc123');

        $result = $this->transformer->write([], $opening)->read();

        static::assertEquals([], $result);
    }

    #[Test]
    public function shouldReadReturnsPhotoDataForValidMultimedia(): void
    {
        $this->thumbor->method('retriveCropBodyTagPicture')->willReturn('thumbnail-url');

        $opening = $this->createMock(Opening::class);
        $opening->method('multimediaId')->willReturn('id1');

        $clipping = $this->createMock(Clipping::class);
        $clipping->method('topLeftX')->willReturn(0);
        $clipping->method('topLeftY')->willReturn(0);
        $clipping->method('bottomRightX')->willReturn(100);
        $clipping->method('bottomRightY')->willReturn(100);

        $clippings = $this->createMock(Clippings::class);
        $clippings->method('clippingByType')->willReturn($clipping);

        $multimedia = $this->createMock(MultimediaPhoto::class);
        $multimedia->method('clippings')->willReturn($clippings);
        $multimedia->method('caption')->willReturn('Test Caption');

        $photo = $this->createMock(Photo::class);
        $photo->method('file')->willReturn('file.jpg');

        /** @var array<string, array{opening: MultimediaPhoto, resource: Photo}> $arrayMultimedia */
        $arrayMultimedia = [
            'id1' => [
                'opening' => $multimedia,
                'resource' => $photo,
            ],
        ];

        $result = $this->transformer->write($arrayMultimedia, $opening)->read();

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('id1', $result['id']);
        $this->assertSame('photo', $result['type']);
        $this->assertSame('Test Caption', $result['caption']);
        $this->assertSame('thumbnail-url', $result['photo']);

        $this->assertArrayHasKey('shots', $result);
        $this->assertInstanceOf(\stdClass::class, $result['shots']);

        $shots = (array) $result['shots'];

        $this->assertArrayHasKey('4:3', $shots);
        $this->assertArrayHasKey('16:9', $shots);
        $this->assertArrayHasKey('3:4', $shots);
        $this->assertArrayHasKey('3:2', $shots);
        $this->assertArrayHasKey('2:3', $shots);

        $this->assertCount(10, $shots['4:3']);
        $this->assertCount(8, $shots['16:9']);
        $this->assertCount(9, $shots['3:4']);
        $this->assertCount(11, $shots['3:2']);
        $this->assertCount(11, $shots['2:3']);

        foreach ($shots as $aspectRatio => $sizesArray) {
            foreach ($sizesArray as $sizeKey => $url) {
                $this->assertSame('thumbnail-url', $url,
                    "Shot for aspect ratio {$aspectRatio} and size {$sizeKey} should be 'thumbnail-url'");
            }
        }
    }

    #[Test]
    public function canTransformShouldReturnMultimediaPhotoClass(): void
    {
        $this->assertSame(MultimediaPhoto::class, $this->transformer->canTransform());
    }
}
