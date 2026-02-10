<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ValueObject;

use App\Infrastructure\Enum\AspectRatioEnum;
use App\Infrastructure\ValueObject\ImageSize;
use App\Infrastructure\ValueObject\ImageSizeCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageSizeCollection::class)]
class ImageSizeCollectionTest extends TestCase
{
    #[Test]
    public function landscapeReturnsThreeSizes(): void
    {
        $collection = ImageSizeCollection::landscape();

        $this->assertCount(3, $collection->sizes());
    }

    #[Test]
    public function landscapeSizesHaveCorrectDimensions(): void
    {
        $collection = ImageSizeCollection::landscape();
        $sizes = $collection->sizes();

        $this->assertSame('202w', $sizes[0]->label);
        $this->assertSame(202, $sizes[0]->width);
        $this->assertSame(152, $sizes[0]->height);

        $this->assertSame('144w', $sizes[1]->label);
        $this->assertSame(144, $sizes[1]->width);
        $this->assertSame(108, $sizes[1]->height);

        $this->assertSame('128w', $sizes[2]->label);
        $this->assertSame(128, $sizes[2]->width);
        $this->assertSame(96, $sizes[2]->height);
    }

    #[Test]
    #[DataProvider('aspectRatioSizeCountProvider')]
    public function forAspectRatioReturnsExpectedCount(AspectRatioEnum $ratio, int $expectedCount): void
    {
        $collection = ImageSizeCollection::forAspectRatio($ratio);

        $this->assertCount($expectedCount, $collection->sizes());
    }

    #[Test]
    public function forAspectRatio4x3HasCorrectFirstSize(): void
    {
        $collection = ImageSizeCollection::forAspectRatio(AspectRatioEnum::RATIO_4_3);
        $sizes = $collection->sizes();

        $this->assertSame('1440w', $sizes[0]->label);
        $this->assertSame(1440, $sizes[0]->width);
        $this->assertSame(1080, $sizes[0]->height);
    }

    #[Test]
    public function sizesAsMapReturnsLabelToSizeMapping(): void
    {
        $collection = ImageSizeCollection::landscape();
        $map = $collection->sizesAsMap();

        $this->assertArrayHasKey('202w', $map);
        $this->assertArrayHasKey('144w', $map);
        $this->assertArrayHasKey('128w', $map);
        $this->assertInstanceOf(ImageSize::class, $map['202w']);
    }

    #[Test]
    public function collectionIsImmutable(): void
    {
        $reflection = new \ReflectionClass(ImageSizeCollection::class);
        $this->assertTrue($reflection->isReadOnly());
    }

    /**
     * @return array<string, array{0: AspectRatioEnum, 1: int}>
     */
    public static function aspectRatioSizeCountProvider(): array
    {
        return [
            '4:3 has 10 sizes' => [AspectRatioEnum::RATIO_4_3, 10],
            '16:9 has 8 sizes' => [AspectRatioEnum::RATIO_16_9, 8],
            '3:4 has 9 sizes' => [AspectRatioEnum::RATIO_3_4, 9],
            '3:2 has 11 sizes' => [AspectRatioEnum::RATIO_3_2, 11],
            '2:3 has 11 sizes' => [AspectRatioEnum::RATIO_2_3, 11],
        ];
    }
}
