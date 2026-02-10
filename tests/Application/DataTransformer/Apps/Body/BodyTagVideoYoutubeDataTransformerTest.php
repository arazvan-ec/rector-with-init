<?php

/**
 * @copyright
 */

namespace App\Tests\Application\DataTransformer\Apps\Body;

use App\Application\DataTransformer\Apps\Body\BodyTagVideoYoutubeDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyTagVideoYoutube;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
#[CoversClass(BodyTagVideoYoutubeDataTransformer::class)]
class BodyTagVideoYoutubeDataTransformerTest extends TestCase
{
    private BodyTagVideoYoutubeDataTransformer $bodyTagVideoYoutubeDataTransformer;

    protected function setUp(): void
    {
        $this->bodyTagVideoYoutubeDataTransformer = new BodyTagVideoYoutubeDataTransformer('https://player.host');
    }

    #[Test]
    public function canTransformShouldReturnBodyTagVideoYoutubeString(): void
    {
        static::assertSame(BodyTagVideoYoutube::class, $this->bodyTagVideoYoutubeDataTransformer->canTransform());
    }

    #[Test]
    public function readShouldReturnExpectedArray(): void
    {
        $expectedArray = [
            'type' => 'bodytagvideoyoutube',
            'id' => 'video123',
            'width' => 640,
            'height' => 360,
            'caption' => 'Sample Caption',
            'start' => 10,
        ];

        $bodyElementMock = $this->createConfiguredMock(BodyTagVideoYoutube::class, $expectedArray);

        $expectedArray['video'] = 'https://player.host/embed/video/video123/640/360/10/';

        $result = $this->bodyTagVideoYoutubeDataTransformer->write($bodyElementMock)->read();

        static::assertSame($expectedArray, $result);
    }
}
