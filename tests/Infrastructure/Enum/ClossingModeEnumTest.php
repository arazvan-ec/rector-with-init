<?php

/**
 * @copyright
 */

namespace App\Tests\Infrastructure\Enum;

use App\Infrastructure\Enum\ClossingModeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(ClossingModeEnum::class)]
class ClossingModeEnumTest extends TestCase
{
    #[Test]
    public function testGetClosingModeById(): void
    {
        $this->assertEquals('registry', ClossingModeEnum::getClosingModeById('1'));
        $this->assertEquals('payment', ClossingModeEnum::getClosingModeById('2'));
        $this->assertEquals('apppayment', ClossingModeEnum::getClosingModeById('3'));
        $this->assertEquals('', ClossingModeEnum::getClosingModeById('4'));
    }
}
