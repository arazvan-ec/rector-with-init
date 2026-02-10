<?php

declare(strict_types=1);

namespace App\Infrastructure\Enum;

use Ec\Multimedia\Domain\Model\ClippingTypes;

enum AspectRatioEnum: string
{
    case RATIO_16_9 = '16:9';
    case RATIO_4_3 = '4:3';
    case RATIO_3_2 = '3:2';
    case RATIO_2_3 = '2:3';
    case RATIO_3_4 = '3:4';

    public function clippingType(): string
    {
        return match ($this) {
            self::RATIO_4_3 => ClippingTypes::SIZE_ARTICLE_4_3,
            default => ClippingTypes::SIZE_MULTIMEDIA_BIG,
        };
    }
}
