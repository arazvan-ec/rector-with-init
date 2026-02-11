<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject;

final readonly class ImageSize
{
    public function __construct(
        public string $label,
        public int $width,
        public int $height,
    ) {
    }
}
