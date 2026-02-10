<?php

/**
 * @copyright
 */

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Service\Thumbor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Thumbor\Url\Builder;
use Thumbor\Url\BuilderFactory;

/**
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 */
#[CoversClass(Thumbor::class)]
class ThumborTest extends TestCase
{
    private Thumbor $thumbor;
    private string $thumborServerUrl = 'http://thumbor-server';
    private string $thumborSecret = 'secret';
    private string $awsBucket = 'aws-bucket';

    protected function setUp(): void
    {
        $this->thumbor = new Thumbor($this->thumborServerUrl, $this->thumborSecret, $this->awsBucket);
    }

    #[Test]
    public function createJournalistImageShouldReturnValidUrl(): void
    {
        $fileImage = '123456789.jpg';
        $expectedPath = $this->awsBucket.'/journalist/123/456/789/'.$fileImage;

        $builderMock = $this->createMock(Builder::class);
        $builderMock->method('__toString')->willReturn('http://thumbor-url');
        $builderFactoryMock = $this->createMock(BuilderFactory::class);
        $builderFactoryMock->method('url')->with($expectedPath)->willReturn($builderMock);

        $reflection = new \ReflectionClass($this->thumbor);
        $property = $reflection->getProperty('thumborFactory');
        $property->setValue($this->thumbor, $builderFactoryMock);

        $result = $this->thumbor->createJournalistImage($fileImage);

        $this->assertEquals('http://thumbor-url', $result);
    }

    #[Test]
    public function retriveCropBodyTagPictureShouldReturnValidCrop(): void
    {
        $toString = 'https://images.ecestaticos.dev/B26-5pH9vylfOiapiBjXanvO7Ho=/615x99:827x381/1440x1920/filters:fill(white):format(jpg)/dev.f.elconfidencial.com/original/0a9/783/99c/0a978399c4be84f3ce367624ca9589ad.jpg';
        $fileImage = '0a978399c4be84f3ce367624ca9589ad.jpg';
        $width = '1440';
        $height = '1920';
        $topX = 615;
        $topY = 99;
        $bottomX = 827;
        $bottomY = 381;
        $expectedPath = $this->awsBucket.'/original/0a9/783/99c/'.$fileImage;

        $builderMock = $this->createMock(Builder::class);

        $thumborArgs = [
            ['resize', [$width, $height]],
            ['crop', [$topX, $topY, $bottomX, $bottomY]],
            ['addFilter', ['fill', 'white']],
            ['addFilter', ['format', 'jpg']],
        ];
        $invokedCount = $this->exactly(4);
        $builderMock
            ->expects($invokedCount)
            ->method('__call')
            ->willReturnCallback(function (string $method) use ($builderMock, $thumborArgs, $invokedCount) {
                self::assertSame($method, $thumborArgs[$invokedCount->numberOfInvocations() - 1][0]);

                return $builderMock;
            });

        $builderMock->method('__toString')->willReturn($toString);
        $builderFactoryMock = $this->createMock(BuilderFactory::class);
        $builderFactoryMock->method('url')->with($expectedPath)->willReturn($builderMock);

        $reflection = new \ReflectionClass($this->thumbor);
        $property = $reflection->getProperty('thumborFactory');
        $property->setValue($this->thumbor, $builderFactoryMock);

        $result = $this->thumbor->retriveCropBodyTagPicture($fileImage, $width, $height, $topX, $topY, $bottomX, $bottomY);

        $this->assertSame($toString, $result);
    }
}
