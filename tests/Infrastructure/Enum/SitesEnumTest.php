<?php

/**
 * @copyright
 */

namespace App\Tests\Infrastructure\Enum;

use App\Infrastructure\Enum\SitesEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(SitesEnum::class)]
class SitesEnumTest extends TestCase
{
    private SitesEnum $elConfidencial;
    private SitesEnum $vanitatis;
    private SitesEnum $alimente;

    protected function setUp(): void
    {
        $this->elConfidencial = SitesEnum::ELCONFIDENCIAL;
        $this->vanitatis = SitesEnum::VANITATIS;
        $this->alimente = SitesEnum::ALIMENTE;
    }

    protected function tearDown(): void
    {
        unset($this->elConfidencial, $this->vanitatis, $this->alimente);
    }

    #[Test]
    public function getHostnameByIdMustReturnDefaultForElConfidencial(): void
    {
        $this->assertSame('elconfidencial', SitesEnum::getHostnameById($this->elConfidencial->value));
    }

    #[Test]
    public function getEncodenameByIdMustReturnDefaultForElConfidencial(): void
    {
        $this->assertSame('el-confidencial', SitesEnum::getEncodenameById($this->elConfidencial->value));
    }

    #[Test]
    public function getHostnameByIdMustReturnCorrectValue(): void
    {
        $this->assertSame('elconfidencial', SitesEnum::getHostnameById('999'));
        $this->assertSame('vanitatis.elconfidencial', SitesEnum::getHostnameById($this->vanitatis->value));
        $this->assertSame('alimente.elconfidencial', SitesEnum::getHostnameById($this->alimente->value));
    }

    #[Test]
    public function getEncodenameByIdMustReturnCorrectValue(): void
    {
        $this->assertSame('el-confidencial', SitesEnum::getEncodenameById('69'));
        $this->assertSame('vanitatis', SitesEnum::getEncodenameById($this->vanitatis->value));
        $this->assertSame('alimente', SitesEnum::getEncodenameById($this->alimente->value));
    }
}
