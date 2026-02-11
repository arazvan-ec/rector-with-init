<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Enum\AspectRatioEnum;
use App\Infrastructure\Service\MultimediaShotService;
use App\Infrastructure\Service\Thumbor;
use App\Infrastructure\ValueObject\ImageSizeCollection;
use Ec\Multimedia\Domain\Model\Clipping;
use Ec\Multimedia\Domain\Model\Clippings;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MultimediaShotService::class)]
class MultimediaShotServiceTest extends TestCase
{
    private MultimediaShotService $service;
    private Thumbor $thumbor;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->service = new MultimediaShotService($this->thumbor);
    }

    #[Test]
    public function generateLandscapeShotsReturnsThreeSizes(): void
    {
        $clipping = $this->createMock(Clipping::class);
        $clipping->method('topLeftX')->willReturn(0);
        $clipping->method('topLeftY')->willReturn(0);
        $clipping->method('bottomRightX')->willReturn(100);
        $clipping->method('bottomRightY')->willReturn(75);

        $clippings = $this->createMock(Clippings::class);
        $clippings->method('clippingByType')
            ->with(ClippingTypes::SIZE_ARTICLE_4_3)
            ->willReturn($clipping);

        $multimedia = $this->createMock(Multimedia::class);
        $multimedia->method('file')->willReturn('test-file.jpg');
        $multimedia->method('clippings')->willReturn($clippings);

        $this->thumbor->expects($this->exactly(3))
            ->method('retriveCropBodyTagPicture')
            ->willReturn('https://thumbor.example.com/image.jpg');

        $shots = $this->service->generateLandscapeShots($multimedia);

        $this->assertCount(3, $shots);
        $this->assertArrayHasKey('202w', $shots);
        $this->assertArrayHasKey('144w', $shots);
        $this->assertArrayHasKey('128w', $shots);
    }

    #[Test]
    public function generateLandscapeShotsFromMediaReturnsThreeSizes(): void
    {
        $clipping = $this->createMock(Clipping::class);
        $clipping->method('topLeftX')->willReturn(0);
        $clipping->method('topLeftY')->willReturn(0);
        $clipping->method('bottomRightX')->willReturn(100);
        $clipping->method('bottomRightY')->willReturn(75);

        $clippings = $this->createMock(Clippings::class);
        $clippings->method('clippingByType')
            ->with(ClippingTypes::SIZE_ARTICLE_4_3)
            ->willReturn($clipping);

        $opening = $this->createMock(MultimediaPhoto::class);
        $opening->method('clippings')->willReturn($clippings);

        $resource = $this->createMock(Photo::class);
        $resource->method('file')->willReturn('photo-file.jpg');

        $this->thumbor->expects($this->exactly(3))
            ->method('retriveCropBodyTagPicture')
            ->willReturn('https://thumbor.example.com/image.jpg');

        $multimediaOpening = [
            'opening' => $opening,
            'resource' => $resource,
        ];

        $shots = $this->service->generateLandscapeShotsFromMedia($multimediaOpening);

        $this->assertCount(3, $shots);
        $this->assertArrayHasKey('202w', $shots);
        $this->assertArrayHasKey('144w', $shots);
        $this->assertArrayHasKey('128w', $shots);
    }

    #[Test]
    public function generateResponsiveShotsReturnsAllAspectRatios(): void
    {
        $clipping = $this->createMock(Clipping::class);
        $clipping->method('topLeftX')->willReturn(0);
        $clipping->method('topLeftY')->willReturn(0);
        $clipping->method('bottomRightX')->willReturn(100);
        $clipping->method('bottomRightY')->willReturn(75);

        $clippings = $this->createMock(Clippings::class);
        $clippings->method('clippingByType')
            ->with(ClippingTypes::SIZE_MULTIMEDIA_BIG)
            ->willReturn($clipping);

        $resource = $this->createMock(Photo::class);
        $resource->method('file')->willReturn('photo-file.jpg');

        $this->thumbor->method('retriveCropBodyTagPicture')
            ->willReturn('https://thumbor.example.com/image.jpg');

        $allShots = $this->service->generateResponsiveShots($resource, $clippings);

        $this->assertCount(5, $allShots);
        $this->assertArrayHasKey('16:9', $allShots);
        $this->assertArrayHasKey('4:3', $allShots);
        $this->assertArrayHasKey('3:2', $allShots);
        $this->assertArrayHasKey('2:3', $allShots);
        $this->assertArrayHasKey('3:4', $allShots);
    }

    #[Test]
    public function generateJournalistPhotoFromBlogPhoto(): void
    {
        $this->thumbor->expects($this->once())
            ->method('createJournalistImage')
            ->with('blog-photo.jpg')
            ->willReturn('https://thumbor.example.com/journalist.jpg');

        $result = $this->service->generateJournalistPhoto('blog-photo.jpg', '');

        $this->assertSame('https://thumbor.example.com/journalist.jpg', $result);
    }

    #[Test]
    public function generateJournalistPhotoFallsBackToRegularPhoto(): void
    {
        $this->thumbor->expects($this->once())
            ->method('createJournalistImage')
            ->with('regular-photo.jpg')
            ->willReturn('https://thumbor.example.com/journalist.jpg');

        $result = $this->service->generateJournalistPhoto('', 'regular-photo.jpg');

        $this->assertSame('https://thumbor.example.com/journalist.jpg', $result);
    }

    #[Test]
    public function generateJournalistPhotoReturnsEmptyWhenNoPhotos(): void
    {
        $this->thumbor->expects($this->never())
            ->method('createJournalistImage');

        $result = $this->service->generateJournalistPhoto('', '');

        $this->assertSame('', $result);
    }
}
