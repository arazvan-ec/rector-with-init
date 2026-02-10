<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagVideoDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyTagVideo;
use Ec\Editorial\Domain\Model\Body\VideoId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
#[CoversClass(BodyTagVideoDataTransformer::class)]
class BodyTagVideoDataTransformerTest extends TestCase
{
    private BodyTagVideoDataTransformer $bodyTagVideoDataTransformer;

    protected function setUp(): void
    {
        $this->bodyTagVideoDataTransformer = new BodyTagVideoDataTransformer('https://player.host');
    }

    #[Test]
    public function canTransformShouldReturnBodyTagVideoString(): void
    {
        static::assertSame(BodyTagVideo::class, $this->bodyTagVideoDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $videIdValue = 'video123';
        $videoIdMock = $this->createMock(VideoId::class);
        $videoIdMock->method('id')->willReturn($videIdValue);
        $expectedArray = [
            'type' => 'bodytagvideo',
            'id' => $videoIdMock,
            'width' => 640,
            'height' => 360,
            'caption' => 'Sample Caption',
        ];

        $bodyElementMock = $this->createConfiguredMock(BodyTagVideo::class, $expectedArray);

        $expectedArray['video'] = 'https://player.host/embed/video/video123/640/360/';
        $expectedArray['id'] = $videIdValue;

        $result = $this->bodyTagVideoDataTransformer->write($bodyElementMock)->read();

        static::assertSame($expectedArray, $result);
    }
}
