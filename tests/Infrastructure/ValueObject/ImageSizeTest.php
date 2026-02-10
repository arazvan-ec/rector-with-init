<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ValueObject;

use App\Infrastructure\ValueObject\ImageSize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageSize::class)]
class ImageSizeTest extends TestCase
{
    #[Test]
    public function canBeConstructedWithLabelWidthHeight(): void
    {
        $size = new ImageSize('414w', 414, 311);

        $this->assertSame('414w', $size->label);
        $this->assertSame(414, $size->width);
        $this->assertSame(311, $size->height);
    }

    #[Test]
    public function isImmutable(): void
    {
        $size = new ImageSize('1440w', 1440, 1080);

        $reflection = new \ReflectionClass($size);
        $this->assertTrue($reflection->isReadOnly());
    }
}
