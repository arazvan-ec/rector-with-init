<?php

/**
 * @copyright
 */

namespace App\Tests;

use App\DependencyInjection\Compiler\BodyDataTransformerCompiler;
use App\DependencyInjection\Compiler\EditorialOrchestratorCompiler;
use App\DependencyInjection\Compiler\MediaDataTransformerCompiler;
use App\DependencyInjection\Compiler\MultimediaFactoryCompiler;
use App\DependencyInjection\Compiler\MultimediaOrchestratorCompiler;
use App\DependencyInjection\Compiler\WidgetDataTransformerCompiler;
use App\DependencyInjection\Compiler\WidgetLegacyCreatorHandlerCompiler;
use App\Kernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
#[CoversClass(Kernel::class)]
class KernelTest extends TestCase
{
    #[Test]
    public function buildAddLandingOrchestratorCompilerPassToContainerBuilder(): void
    {
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $invokedCount = self::exactly(7);
        $containerBuilder->expects($invokedCount)
            ->method('addCompilerPass')
            ->willReturnCallback(function ($method) use ($containerBuilder, $invokedCount) {
                if (1 == $invokedCount->numberOfInvocations()) {
                    self::assertInstanceOf(EditorialOrchestratorCompiler::class, $method);
                } elseif (2 == $invokedCount->numberOfInvocations()) {
                    self::assertInstanceOf(BodyDataTransformerCompiler::class, $method);
                } elseif (3 == $invokedCount->numberOfInvocations()) {
                    self::assertInstanceOf(MultimediaFactoryCompiler::class, $method);
                } elseif (4 == $invokedCount->numberOfInvocations()) {
                    self::assertInstanceOf(MediaDataTransformerCompiler::class, $method);
                } elseif (5 == $invokedCount->numberOfInvocations()) {
                    self::assertInstanceOf(MultimediaOrchestratorCompiler::class, $method);
                } elseif (6 == $invokedCount->numberOfInvocations()) {
                    self::assertInstanceOf(WidgetLegacyCreatorHandlerCompiler::class, $method);
                } elseif (7 == $invokedCount->numberOfInvocations()) {
                    self::assertInstanceOf(WidgetDataTransformerCompiler::class, $method);
                }

                return $containerBuilder;
            });

        $kernel = $this->getMockBuilder(Kernel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('build');

        $method->invoke($kernel, $containerBuilder);
    }
}
