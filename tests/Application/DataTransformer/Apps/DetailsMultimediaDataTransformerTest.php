<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps;

use App\Application\DataTransformer\Apps\DetailsMultimediaDataTransformer;
use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Multimedia\Domain\Model\Clipping;
use Ec\Multimedia\Domain\Model\Clippings;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class DetailsMultimediaDataTransformerTest extends TestCase
{
    private DetailsMultimediaDataTransformer $transformer;

    /** @var Thumbor|MockObject */
    private Thumbor $thumbor;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->transformer = new DetailsMultimediaDataTransformer($this->thumbor);
    }

    #[Test]
    public function writeAndReadShouldReturnCorrectArray(): void
    {
        $multimedia = $this->createMock(Multimedia::class);
        $clippings = $this->getMockBuilder(Clippings::class)
            ->onlyMethods(['clippingByType'])
            ->getMock();
        $clipping = $this->createMock(Clipping::class);

        $multimedia->expects($this->once())
            ->method('clippings')
            ->willReturn($clippings);

        $clippings->expects($this->once())
            ->method('clippingByType')
            ->with(ClippingTypes::SIZE_MULTIMEDIA_BIG)
            ->willReturn($clipping);

        $clipping->expects($this->exactly(27))
            ->method('topLeftX')
            ->willReturn(0);
        $clipping->expects($this->exactly(27))
            ->method('topLeftY')
            ->willReturn(0);
        $clipping->expects($this->exactly(27))
            ->method('bottomRightX')
            ->willReturn(1920);
        $clipping->expects($this->exactly(27))
            ->method('bottomRightY')
            ->willReturn(1080);

        $this->thumbor->expects($this->exactly(27))
            ->method('retriveCropBodyTagPicture')
            ->willReturn('https://example.com/image.jpg');

        $expectedCaption = 'Test caption';
        $expectedId = '123';

        $multimedia->expects($this->once())
            ->method('caption')
            ->willReturn($expectedCaption);

        $multimedia->expects($this->once())
            ->method('id')
            ->willReturn($expectedId);

        $openingMultimedia = $this->createMock(PhotoExist::class);
        $openingMultimediaId = $this->createMock(MultimediaId::class);
        $openingMultimediaId->method('id')
            ->willReturn($expectedId);

        $openingMultimedia->method('id')
            ->willReturn($openingMultimediaId);

        $this->transformer->write([$expectedId => $multimedia], $openingMultimedia);
        $result = $this->transformer->read();

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals($expectedId, $result['id']);

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('photo', $result['type']);

        $this->assertArrayHasKey('caption', $result);
        $this->assertEquals($expectedCaption, $result['caption']);

        $this->assertArrayHasKey('shots', $result);
        $this->assertIsObject($result['shots']);

        $this->assertArrayHasKey('photo', $result);
        $this->assertEquals('https://example.com/image.jpg', $result['photo']);
    }

    #[Test]
    public function writeAndReadShouldReturnEmptyPhoto(): void
    {
        $multimedia = $this->createMock(Multimedia::class);
        $clippings = $this->getMockBuilder(Clippings::class)
            ->onlyMethods(['clippingByType'])
            ->getMock();
        $clipping = $this->createMock(Clipping::class);

        $multimedia->expects($this->once())
            ->method('clippings')
            ->willReturn($clippings);

        $clippings->expects($this->once())
            ->method('clippingByType')
            ->with(ClippingTypes::SIZE_MULTIMEDIA_BIG)
            ->willReturn($clipping);

        $clipping->expects($this->exactly(27))
            ->method('topLeftX');
        $clipping->expects($this->exactly(27))
            ->method('topLeftY');
        $clipping->expects($this->exactly(27))
            ->method('bottomRightX');
        $clipping->expects($this->exactly(27))
            ->method('bottomRightY');

        $this->thumbor->expects($this->exactly(27))
            ->method('retriveCropBodyTagPicture');

        $expectedCaption = 'Test caption';
        $expectedId = '123';

        $multimedia->expects($this->once())
            ->method('caption')
            ->willReturn($expectedCaption);

        $multimedia->expects($this->once())
            ->method('id')
            ->willReturn($expectedId);

        $openingMultimedia = $this->createMock(PhotoExist::class);
        $openingMultimediaId = $this->createMock(MultimediaId::class);
        $openingMultimediaId->method('id')
            ->willReturn($expectedId);

        $openingMultimedia->method('id')
            ->willReturn($openingMultimediaId);

        $this->transformer->write([$expectedId => $multimedia], $openingMultimedia);
        $result = $this->transformer->read();

        $this->assertArrayHasKey('photo', $result);
        $this->assertEquals('', $result['photo']);
    }

    #[Test]
    public function writeAndReadShouldReturnMultimediaNull(): void
    {
        $expectedId = '1';
        /** @var Multimedia|MockObject $multimedia */
        $multimedia = $this->createMock(Multimedia::class);
        $openingMultimedia = $this->createMock(PhotoExist::class);
        $openingMultimediaId = $this->createMock(MultimediaId::class);
        $openingMultimediaId->method('id')
            ->willReturn($expectedId);

        $openingMultimedia->method('id')
            ->willReturn($openingMultimediaId);

        $this->transformer->write(['2' => $multimedia], $openingMultimedia);
        $result = $this->transformer->read();

        static::assertSame([], $result);
    }
}
