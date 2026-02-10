<?php

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Service\PictureShots;
use App\Infrastructure\Service\Thumbor;
use App\Tests\Infrastructure\Service\DataProvider\PictureShotsDataProvider;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureId;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PictureShotsTest extends TestCase
{
    /**
     * @var Thumbor|MockObject
     */
    private Thumbor $thumbor;
    private PictureShots $pictureShot;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->pictureShot = new PictureShots($this->thumbor);
    }

    /**
     * @param array<string, mixed> $resolveData
     * @param array<string, mixed> $shots
     * @param array<string, mixed> $sizes
     */
    #[DataProviderExternal(PictureShotsDataProvider::class, 'getDataShots')]
    #[Test]
    public function retrieveShotsByPhotoIdShouldReturnValidArray(
        string $idPhoto,
        array $resolveData,
        array $shots,
        array $sizes,
        string $photoFile,
        int $topX,
        int $topY,
        int $bottomX,
        int $bottomY,
        string $caption,
        string $alternate,
        string $orientation,
    ): void {
        $bodytagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodytagPictureId->method('id')->willReturn($idPhoto);

        $bodyElement = $this->createMock(BodyTagPicture::class);
        $bodyElement->method('id')->willReturn($bodytagPictureId);
        $bodyElement->method('topX')->willReturn($topX);
        $bodyElement->method('topY')->willReturn($topY);
        $bodyElement->method('bottomX')->willReturn($bottomX);
        $bodyElement->method('bottomY')->willReturn($bottomY);
        $bodyElement->method('caption')->willReturn($caption);
        $bodyElement->method('alternate')->willReturn($alternate);
        $bodyElement->method('orientation')->willReturn($orientation);

        $resolveDataMock = [];
        $photo = $this->createMock(Photo::class);
        $photo->method('file')->willReturn($photoFile);
        $resolveDataMock['photoFromBodyTags'] = [$idPhoto => $photo];

        $expectedCalls = [];
        foreach ($shots as $ratio => $url) {
            /** @var array<string, int> $ratioData */
            $ratioData = $sizes[$ratio];
            $expectedCalls[] = [
                'params' => [
                    $photoFile,
                    $ratioData['width'],
                    $ratioData['height'],
                    $topX,
                    $topY,
                    $bottomX,
                    $bottomY,
                ],
                'return' => $url,
            ];
        }
        $callIndex = 0;
        $this->thumbor
            ->expects(static::exactly(\count($shots)))
            ->method('retriveCropBodyTagPicture')
            ->willReturnCallback(function (
                $file, $width, $height, $tX, $tY, $bX, $bY,
            ) use ($expectedCalls, &$callIndex) {
                static::assertLessThan(
                    \count($expectedCalls),
                    $callIndex,
                    'More calls received than expected'
                );

                $expectedParams = $expectedCalls[$callIndex]['params'];
                static::assertEquals($expectedParams[0], $file);
                static::assertEquals($expectedParams[1], $width);
                static::assertEquals($expectedParams[2], $height);
                static::assertEquals($expectedParams[3], $tX);
                static::assertEquals($expectedParams[4], $tY);
                static::assertEquals($expectedParams[5], $bX);
                static::assertEquals($expectedParams[6], $bY);

                return $expectedCalls[$callIndex++]['return'];
            });

        $result = $this->pictureShot->retrieveShotsByPhotoId($resolveDataMock, $bodyElement);

        foreach ($shots as $ratio => $url) {
            $this->assertEquals($url, $result[$ratio]);
        }
    }

    /**
     * @param array<string, mixed> $resolveData
     * @param array<string, mixed> $expected
     */
    #[DataProviderExternal(PictureShotsDataProvider::class, 'getDataEmpty')]
    #[Test]
    public function retrieveShotsByPhotoIdShouldReturnEmptyArray(
        string $idPhoto,
        array $resolveData,
        array $expected,
    ): void {
        $resolveDataMock = [];
        $bodyElement = $this->createMock(BodyTagPicture::class);
        if (isset($resolveData['photoFromBodyTags'])) {
            $bodytagPictureId = $this->createMock(BodyTagPictureId::class);
            /** @var array{id: array{id:string}} $photoFromBodyTags */
            $photoFromBodyTags = $resolveData['photoFromBodyTags'];
            $bodytagPictureId->method('id')->willReturn($photoFromBodyTags['id']['id']);
            $bodyElement->method('id')->willReturn($bodytagPictureId);
            $photo = $this->createMock(Photo::class);
            $resolveDataMock['photoFromBodyTags'] = [$idPhoto => $photo];
        }

        $result = $this->pictureShot->retrieveShotsByPhotoId($resolveDataMock, $bodyElement);

        $this->assertEquals($expected, $result);
    }
}
