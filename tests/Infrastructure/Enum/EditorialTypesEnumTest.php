<?php

/**
 * @copyright
 */

namespace App\Tests\Infrastructure\Enum;

use App\Infrastructure\Enum\EditorialTypesEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(EditorialTypesEnum::class)]
class EditorialTypesEnumTest extends TestCase
{
    #[Test]
    public function getNameById(): void
    {
        $this->assertEquals(['id' => '1', 'name' => 'news'], EditorialTypesEnum::getNameById('news'));
        $this->assertEquals(['id' => '3', 'name' => 'blog'], EditorialTypesEnum::getNameById('blog'));
        $this->assertEquals(['id' => '12', 'name' => 'livesport'], EditorialTypesEnum::getNameById('livesport'));
        $this->assertEquals(['id' => '13', 'name' => 'live'], EditorialTypesEnum::getNameById('live'));
        $this->assertEquals(['id' => '14', 'name' => 'chronicle'], EditorialTypesEnum::getNameById('chronicle'));
        $this->assertEquals(['id' => '15', 'name' => 'lovers'], EditorialTypesEnum::getNameById('lovers'));
        $this->assertEquals(['id' => '1', 'name' => 'news'], EditorialTypesEnum::getNameById('unknown'));
    }
}
