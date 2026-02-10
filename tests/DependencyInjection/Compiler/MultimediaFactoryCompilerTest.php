<?php

/**
 * @copyright
 */

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\MultimediaFactoryCompiler;
use Ec\Multimedia\Application\Factory\Multimedia;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Ken Serikawa <kserikawa@ext.elconfidencial.com>
 */
#[CoversClass(MultimediaFactoryCompiler::class)]
class MultimediaFactoryCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function process(): void
    {
        $orchestratorDefinition = new Definition();
        $orchestratorDefinition->addTag('ec.multimedia.factory');
        $this->setDefinition(Multimedia::class, $orchestratorDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            Multimedia::class,
            'addFactory',
            [
                $orchestratorDefinition,
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $orchestratorChainDefinition = new Definition(Multimedia::class);
        $container->setDefinition(Multimedia::class, $orchestratorChainDefinition);

        $container->addCompilerPass(new MultimediaFactoryCompiler());
    }
}
