<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Enum;

use App\Infrastructure\Enum\AspectRatioEnum;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AspectRatioEnum::class)]
class AspectRatioEnumTest extends TestCase
{
    #[Test]
    public function enumHasFiveCases(): void
    {
        $cases = AspectRatioEnum::cases();

        $this->assertCount(5, $cases);
    }

    #[Test]
    #[DataProvider('aspectRatioValuesProvider')]
    public function enumValuesMatchExpectedStrings(AspectRatioEnum $case, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $case->value);
    }

    #[Test]
    public function clippingTypeForLandscapeRatios(): void
    {
        $this->assertSame(
            ClippingTypes::SIZE_ARTICLE_4_3,
            AspectRatioEnum::RATIO_4_3->clippingType()
        );
    }

    #[Test]
    public function clippingTypeForOpeningRatios(): void
    {
        $this->assertSame(
            ClippingTypes::SIZE_MULTIMEDIA_BIG,
            AspectRatioEnum::RATIO_16_9->clippingType()
        );

        $this->assertSame(
            ClippingTypes::SIZE_MULTIMEDIA_BIG,
            AspectRatioEnum::RATIO_3_2->clippingType()
        );

        $this->assertSame(
            ClippingTypes::SIZE_MULTIMEDIA_BIG,
            AspectRatioEnum::RATIO_2_3->clippingType()
        );

        $this->assertSame(
            ClippingTypes::SIZE_MULTIMEDIA_BIG,
            AspectRatioEnum::RATIO_3_4->clippingType()
        );
    }

    /**
     * @return array<string, array{0: AspectRatioEnum, 1: string}>
     */
    public static function aspectRatioValuesProvider(): array
    {
        return [
            '16:9' => [AspectRatioEnum::RATIO_16_9, '16:9'],
            '4:3' => [AspectRatioEnum::RATIO_4_3, '4:3'],
            '3:2' => [AspectRatioEnum::RATIO_3_2, '3:2'],
            '2:3' => [AspectRatioEnum::RATIO_2_3, '2:3'],
            '3:4' => [AspectRatioEnum::RATIO_3_4, '3:4'],
        ];
    }
}
