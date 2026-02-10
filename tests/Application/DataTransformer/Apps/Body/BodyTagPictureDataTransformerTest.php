<?php

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagPictureDataTransformer;
use App\Infrastructure\Service\PictureShots;
use App\Tests\Application\DataTransformer\Apps\Body\DataProvider\BodyTagPictureDataProvider;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureId;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BodyTagPictureDataTransformerTest extends TestCase
{
    private BodyTagPictureDataTransformer $dataTransformer;
    /**
     * @var PictureShots|MockObject
     */
    private PictureShots $pictureShots;

    protected function setUp(): void
    {
        $this->pictureShots = $this->createMock(PictureShots::class);
        $this->dataTransformer = new BodyTagPictureDataTransformer($this->pictureShots);
    }

    /**
     * @param array<string, string> $shots
     */
    #[DataProviderExternal(BodyTagPictureDataProvider::class, 'getData')]
    #[Test]
    public function readShouldReturnExpectedArray(
        array $shots,
        string $caption,
        string $alternate,
        string $orientation,
        string $url,
        string $expectedCaption,
    ): void {
        $this->pictureShots->method('retrieveShotsByPhotoId')->willReturn($shots);
        $bodytagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodytagPictureId->method('id')->willReturn('123');

        $bodyElement = $this->createMock(BodyTagPicture::class);
        $bodyElement->method('id')->willReturn($bodytagPictureId);

        $bodyElement->method('caption')->willReturn($caption);
        $bodyElement->method('alternate')->willReturn($alternate);
        $bodyElement->method('orientation')->willReturn($orientation);

        $result = $this->dataTransformer->write($bodyElement)->read();

        $this->assertEquals($shots, $result['shots']);
        $this->assertEquals($expectedCaption, $result['caption']);
        $this->assertEquals($alternate, $result['alternate']);
        $this->assertEquals($orientation, $result['orientation']);
        $this->assertEquals($url, $result['url']);
    }

    #[Test]
    public function canTransformShouldReturnBodyTagPictureString(): void
    {
        $this->assertEquals(BodyTagPicture::class, $this->dataTransformer->canTransform());
    }
}
