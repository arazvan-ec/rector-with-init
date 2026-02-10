<?php

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagPictureMembershipDataTransformer;
use App\Infrastructure\Service\PictureShots;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureId;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BodyTagPictureMembershipDataTransformerTest extends TestCase
{
    /**
     * @var PictureShots|MockObject
     */
    private PictureShots $pictureShots;
    private BodyTagPictureMembershipDataTransformer $dataTransformer;

    protected function setUp(): void
    {
        $this->pictureShots = $this->createMock(PictureShots::class);
        $this->dataTransformer = new BodyTagPictureMembershipDataTransformer($this->pictureShots);
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $shots = [
            '1440w' => 'https://images.ecestaticos.dev/B26-5pH9vylfOiapiBjXanvO7Ho=/615x99:827x381/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '1200w' => 'https://images.ecestaticos.dev/gN2tLeVBCOcV5AKBmZeJhGYztTk=/615x99:827x381/1200x1600/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '996w' => 'https://images.ecestaticos.dev/YRLxy6ChIKjekgdg_BN1DirWtJ8=/615x99:827x381/996x1328/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '640w' => 'https://images.ecestaticos.dev/WByyZwZDIXdsAikGvHjMd3wOiUI=/615x99:827x381/560x747/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '390w' => 'https://images.ecestaticos.dev/6LRdLT09KxKdAIaRQV6gbHtiZSQ=/615x99:827x381/390x520/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '568w' => 'https://images.ecestaticos.dev/m70h5OCBdQyGjYRqai5qmRVZoUQ=/615x99:827x381/568x757/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '382w' => 'https://images.ecestaticos.dev/ws_0oo3JORfvWxI_XKyluvDeGRI=/615x99:827x381/382x509/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
            '328w' => 'https://images.ecestaticos.dev/YsYE5tLIS_WX3BU6agIfeikYUl8=/615x99:827x381/328x437/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg',
        ];
        $orientation = 'landscape';
        $url = 'https://images.ecestaticos.dev/B26-5pH9vylfOiapiBjXanvO7Ho=/615x99:827x381/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg';

        $this->pictureShots->method('retrieveShotsByPhotoId')->willReturn($shots);
        $bodytagPictureId = $this->createMock(BodyTagPictureId::class);
        $bodytagPictureId->method('id')->willReturn('123');

        $bodyElement = $this->createMock(BodyTagPictureMembership::class);
        $bodyElement->method('id')->willReturn($bodytagPictureId);

        $bodyElement->method('orientation')->willReturn($orientation);

        $result = $this->dataTransformer->write($bodyElement, [])->read();

        $this->assertEquals($shots, $result['shots']);
        $this->assertEquals($orientation, $result['orientation']);
        $this->assertEquals($url, $result['url']);
    }

    #[Test]
    public function canTransformShouldReturnBodyTagPictureString(): void
    {
        $this->assertEquals(BodyTagPictureMembership::class, $this->dataTransformer->canTransform());
    }
}
