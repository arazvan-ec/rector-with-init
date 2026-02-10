<?php

/**
 * @copyright
 */

namespace App\Tests\Infrastructure\Trait;

use App\Infrastructure\Enum\SitesEnum;
use App\Infrastructure\Trait\UrlGeneratorTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(UrlGeneratorTrait::class)]
class UrlGeneratorTraitTest extends TestCase
{
    private MockObject $trait;

    protected function setUp(): void
    {
        $this->trait = $this->getMockForTrait(UrlGeneratorTrait::class);
    }

    #[Test]
    public function setAndGetExtension(): void
    {
        $reflection = new \ReflectionClass($this->trait);
        $setExtensionMethod = $reflection->getMethod('setExtension');
        $setExtensionMethod->invoke($this->trait, 'dev');
        $extensionMethod = $reflection->getMethod('extension');

        $this->assertSame('dev', $extensionMethod->invoke($this->trait));
    }

    #[Test]
    public function generateUrlMustGenerateCorrectUrlWhenIsNotBlog(): void
    {
        $reflection = new \ReflectionClass($this->trait);
        $setExtensionMethod = $reflection->getMethod('setExtension');
        $setExtensionMethod->invoke($this->trait, 'dev');

        $format = 'https://%s.%s.%s/%s';
        $subdomain = 'www';
        $siteId = SitesEnum::ELCONFIDENCIAL->value;
        $urlPath = '/españa/andalucia';

        $expectedUrl = 'https://www.elconfidencial.dev/españa/andalucia';

        $generateUrlMethod = $reflection->getMethod('generateUrl');
        $result = $generateUrlMethod->invoke($this->trait, $format, $subdomain, $siteId, $urlPath);

        $this->assertSame($expectedUrl, $result);
    }

    #[Test]
    public function generateUrlMustGenerateCorrectUrlWhenIsBlog(): void
    {
        $reflection = new \ReflectionClass($this->trait);
        $setExtensionMethod = $reflection->getMethod('setExtension');
        $setExtensionMethod->invoke($this->trait, 'dev');

        $format = 'https://%s.%s.%s/%s';
        $subdomain = 'blog';
        $siteId = SitesEnum::ELCONFIDENCIAL->value;
        $urlPath = '/españa/andalucia';

        $expectedUrl = 'https://blog.elconfidencial.dev/españa/andalucia';

        $generateUrlMethod = $reflection->getMethod('generateUrl');
        $result = $generateUrlMethod->invoke($this->trait, $format, $subdomain, $siteId, $urlPath);

        $this->assertSame($expectedUrl, $result);
    }
}
