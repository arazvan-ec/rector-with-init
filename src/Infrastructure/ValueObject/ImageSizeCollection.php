<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject;

use App\Infrastructure\Enum\AspectRatioEnum;

final readonly class ImageSizeCollection
{
    /**
     * @param ImageSize[] $sizes
     */
    private function __construct(
        private array $sizes,
    ) {
    }

    /**
     * Landscape thumbnail sizes (202w, 144w, 128w).
     * Used by: recommended editorials, inserted news, body tag pictures.
     */
    public static function landscape(): self
    {
        return new self([
            new ImageSize('202w', 202, 152),
            new ImageSize('144w', 144, 108),
            new ImageSize('128w', 128, 96),
        ]);
    }

    /**
     * Responsive sizes for a specific aspect ratio.
     * Used by: DetailsMultimediaPhotoDataTransformer (opening multimedia).
     */
    public static function forAspectRatio(AspectRatioEnum $ratio): self
    {
        return match ($ratio) {
            AspectRatioEnum::RATIO_4_3 => self::ratio4x3(),
            AspectRatioEnum::RATIO_16_9 => self::ratio16x9(),
            AspectRatioEnum::RATIO_3_4 => self::ratio3x4(),
            AspectRatioEnum::RATIO_3_2 => self::ratio3x2(),
            AspectRatioEnum::RATIO_2_3 => self::ratio2x3(),
        };
    }

    /**
     * @return ImageSize[]
     */
    public function sizes(): array
    {
        return $this->sizes;
    }

    /**
     * @return array<string, ImageSize>
     */
    public function sizesAsMap(): array
    {
        $map = [];
        foreach ($this->sizes as $size) {
            $map[$size->label] = $size;
        }

        return $map;
    }

    private static function ratio4x3(): self
    {
        return new self([
            new ImageSize('1440w', 1440, 1080),
            new ImageSize('1200w', 1200, 900),
            new ImageSize('996w', 996, 747),
            new ImageSize('557w', 557, 418),
            new ImageSize('381w', 381, 286),
            new ImageSize('600w', 600, 450),
            new ImageSize('414w', 414, 311),
            new ImageSize('375w', 375, 281),
            new ImageSize('360w', 360, 270),
            new ImageSize('767w', 767, 575),
        ]);
    }

    private static function ratio16x9(): self
    {
        return new self([
            new ImageSize('1440w', 1440, 810),
            new ImageSize('1200w', 1200, 675),
            new ImageSize('972w', 972, 547),
            new ImageSize('720w', 720, 405),
            new ImageSize('600w', 600, 338),
            new ImageSize('414w', 414, 233),
            new ImageSize('375w', 375, 211),
            new ImageSize('360w', 360, 203),
        ]);
    }

    private static function ratio3x4(): self
    {
        return new self([
            new ImageSize('1440w', 1440, 1920),
            new ImageSize('1200w', 1200, 1600),
            new ImageSize('996w', 996, 1328),
            new ImageSize('391w', 391, 521),
            new ImageSize('300w', 300, 400),
            new ImageSize('600w', 600, 800),
            new ImageSize('414w', 414, 552),
            new ImageSize('375w', 375, 500),
            new ImageSize('360w', 360, 480),
        ]);
    }

    private static function ratio3x2(): self
    {
        return new self([
            new ImageSize('1440w', 1440, 960),
            new ImageSize('1200w', 1200, 800),
            new ImageSize('996w', 996, 664),
            new ImageSize('557w', 557, 371),
            new ImageSize('381w', 381, 254),
            new ImageSize('600w', 600, 400),
            new ImageSize('414w', 414, 276),
            new ImageSize('375w', 375, 250),
            new ImageSize('360w', 360, 240),
            new ImageSize('767w', 767, 511),
            new ImageSize('lo-res', 48, 32),
        ]);
    }

    private static function ratio2x3(): self
    {
        return new self([
            new ImageSize('1440w', 1440, 2160),
            new ImageSize('1200w', 1200, 1800),
            new ImageSize('996w', 996, 1494),
            new ImageSize('557w', 557, 835),
            new ImageSize('381w', 381, 571),
            new ImageSize('600w', 600, 900),
            new ImageSize('414w', 414, 621),
            new ImageSize('375w', 375, 562),
            new ImageSize('360w', 360, 540),
            new ImageSize('767w', 767, 1150),
            new ImageSize('lo-res', 48, 72),
        ]);
    }
}
